<?php

namespace App\Services\Leaders;

use App\Enums\Section;
use App\Http\Integrations\Osm\OsmConnector;
use App\Http\Integrations\Osm\Requests\ListMembersRequest;
use App\Jobs\ImportOsmLeaderPhoto;
use App\Models\Leader;
use App\Services\WaitingList\Osm\OsmAuthenticatorManager;
use App\Settings\OsmSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Saloon\Contracts\OAuthAuthenticator;
use Saloon\Http\Response;

class ImportOsmSectionLeaders
{
    public function __construct(private readonly OsmAuthenticatorManager $authenticatorManager) {}

    /**
     * @return array{created: int, skipped: int, checked: int, failed_sections: list<string>}
     */
    public function __invoke(OsmSettings $osmSettings): array
    {
        $mappings = $this->mappedSections($osmSettings);

        if ($mappings === []) {
            return ['created' => 0, 'skipped' => 0, 'checked' => 0, 'failed_sections' => []];
        }

        $connector = new OsmConnector;
        $authenticator = $this->authenticatorManager->resolveAuthenticator($connector);

        $created = 0;
        $skipped = 0;
        $checked = 0;
        $failedSections = [];

        foreach ($mappings as $sectionMapping) {
            if ($sectionMapping['term_id'] === null) {
                $failedSections[] = $sectionMapping['section']->value.' (No current OSM term found.)';

                continue;
            }

            try {
                $response = $this->sendWithRefresh(
                    connector: $connector,
                    authenticator: $authenticator,
                    send: fn (OsmConnector $connector, OAuthAuthenticator $authenticator): Response => $connector
                        ->authenticate($authenticator)
                        ->send(new ListMembersRequest(
                            sectionId: $sectionMapping['osm_section_id'],
                            termId: $sectionMapping['term_id'],
                        )),
                );
            } catch (RuntimeException $exception) {
                $failedSections[] = $sectionMapping['section']->value.' ('.$exception->getMessage().')';

                continue;
            }

            foreach ($this->extractMemberRows($response->json()) as $memberRow) {
                if (! $this->isLeaderRow($memberRow)) {
                    continue;
                }

                $checked++;

                $name = $this->nameFromRow($memberRow);

                if ($name === null) {
                    continue;
                }

                $existingLeader = Leader::query()
                    ->with('media')
                    ->where('name', $name)
                    ->first();

                if ($existingLeader instanceof Leader) {
                    if (! $existingLeader->hasMedia('photo')) {
                        $this->queueOsmPhotoImport(
                            leader: $existingLeader,
                            memberRow: $memberRow,
                        );
                    }

                    $skipped++;

                    continue;
                }

                $leader = DB::transaction(function () use ($name, $sectionMapping): Leader {
                    $leader = Leader::query()->create([
                        'name' => $name,
                        'bio' => 'This profile was imported from OSM. Add a public biography and photo before activating it.',
                        'is_active' => false,
                    ]);

                    $leader->sectionAssignments()->create([
                        'section' => $sectionMapping['section'],
                    ]);

                    return $leader;
                });

                $this->queueOsmPhotoImport(
                    leader: $leader,
                    memberRow: $memberRow,
                );

                $created++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'checked' => $checked,
            'failed_sections' => $failedSections,
        ];
    }

    /**
     * @return list<array{section: Section, osm_section_id: string, term_id: string|null}>
     */
    private function mappedSections(OsmSettings $osmSettings): array
    {
        return collect($osmSettings->publicSectionMappings())
            ->map(function (string $osmSectionId, string $section) use ($osmSettings): ?array {
                if (blank($osmSectionId) || ! Section::tryFrom($section) instanceof Section) {
                    return null;
                }

                return [
                    'section' => Section::from($section),
                    'osm_section_id' => $osmSectionId,
                    'term_id' => $this->currentTermIdFor($osmSettings, $osmSectionId),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  callable(OsmConnector, OAuthAuthenticator): Response  $send
     */
    private function sendWithRefresh(OsmConnector $connector, OAuthAuthenticator &$authenticator, callable $send): Response
    {
        $response = $send($connector, $authenticator);

        if ($response->status() === 401) {
            $authenticator = $this->authenticatorManager->forceRefreshAuthenticator($connector);
            $response = $send($connector, $authenticator);
        }

        $this->guardResponse($response);

        if ($response->failed()) {
            throw new RuntimeException('OSM member-list request failed with HTTP '.$response->status().'.');
        }

        return $response;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractMemberRows(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $items = $payload['items']
            ?? $payload['data']['items']
            ?? $payload['data']
            ?? $payload;

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isLeaderRow(array $row): bool
    {
        $active = $row['active'] ?? true;

        if ($active === false || $active === 0 || $active === '0') {
            return false;
        }

        $patrolId = (string) ($row['patrolid'] ?? $row['patrol_id'] ?? '');
        $patrol = Str::of((string) ($row['patrol'] ?? $row['patrol_name'] ?? $row['patrolname'] ?? ''))->lower();
        $role = Str::of((string) ($row['role'] ?? $row['section_role'] ?? $row['sectionRole'] ?? $row['patrol_role'] ?? ''))->lower();
        $sectionType = Str::of((string) ($row['section'] ?? $row['section_type'] ?? $row['sectionType'] ?? ''))->lower();
        $isAdult = $row['is_adult'] ?? $row['isAdult'] ?? false;

        return in_array($patrolId, ['-2', '-3'], true)
            || $patrol->contains(['leader', 'section team'])
            || $role->contains(['leader', 'section team member', 'section team leader'])
            || $sectionType->is('adults')
            || $isAdult === true
            || $isAdult === 1
            || $isAdult === '1';
    }

    private function currentTermIdFor(OsmSettings $osmSettings, string $osmSectionId): ?string
    {
        $today = now()->toImmutable()->startOfDay();

        foreach ($osmSettings->directoryTermsBySection()[$osmSectionId] ?? [] as $termId => $label) {
            if (! is_string($label)) {
                continue;
            }

            if (! preg_match('/\((\d{4}-\d{2}-\d{2}) to (\d{4}-\d{2}-\d{2})\)$/', $label, $matches)) {
                continue;
            }

            $startsAt = CarbonImmutable::parse($matches[1])->startOfDay();
            $endsAt = CarbonImmutable::parse($matches[2])->endOfDay();

            if ($today->betweenIncluded($startsAt, $endsAt)) {
                return (string) $termId;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function nameFromRow(array $row): ?string
    {
        $fullName = $row['fullname'] ?? $row['full_name'] ?? $row['name'] ?? null;

        if (is_scalar($fullName) && filled((string) $fullName)) {
            return trim((string) $fullName);
        }

        $name = trim(implode(' ', array_filter([
            is_scalar($row['firstname'] ?? null) ? (string) $row['firstname'] : null,
            is_scalar($row['lastname'] ?? null) ? (string) $row['lastname'] : null,
        ])));

        return filled($name) ? $name : null;
    }

    private function guardResponse(Response $response): void
    {
        $blockedReason = $response->header('X-Blocked');

        if (is_string($blockedReason) && $blockedReason !== '') {
            throw new RuntimeException('OSM has blocked this application for the connected user: '.$blockedReason);
        }
    }

    /**
     * @param  array<string, mixed>  $memberRow
     */
    private function queueOsmPhotoImport(
        Leader $leader,
        array $memberRow,
    ): void {
        $scoutId = $memberRow['scoutid'] ?? $memberRow['scout_id'] ?? null;
        $photoGuid = $memberRow['photo_guid'] ?? null;

        if (! is_scalar($scoutId) || ! is_scalar($photoGuid) || blank((string) $photoGuid)) {
            return;
        }

        $photoGuid = (string) $photoGuid;

        if ($photoGuid === '00000000-0000-0000-0000-000000000000') {
            return;
        }

        ImportOsmLeaderPhoto::dispatch(
            leaderId: $leader->id,
            scoutId: (string) $scoutId,
            photoGuid: $photoGuid,
        );
    }
}
