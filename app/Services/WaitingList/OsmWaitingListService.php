<?php

namespace App\Services\WaitingList;

use App\Http\Integrations\Osm\OsmConnector;
use App\Http\Integrations\Osm\Requests\CreateMemberRequest;
use App\Models\WaitingListEntry;
use App\Services\WaitingList\Osm\OsmAuthenticatorManager;
use App\Settings\OsmSettings;
use RuntimeException;
use Saloon\Http\Response;

class OsmWaitingListService
{
    public function __construct(
        private readonly OsmSettings $osmSettings,
        private readonly OsmAuthenticatorManager $authenticatorManager,
    ) {}

    /**
     * @return array{firstname: string, lastname: string, dob: string, startedsection: string, started: string, sectionid: string, originating_section_id: string, term_id?: string}
     */
    public function buildPayload(WaitingListEntry $entry): array
    {
        $section = $entry->section();

        if ($section === null) {
            throw new RuntimeException('The waiting-list section is not valid.');
        }

        $mapping = $this->osmSettings->waitingListMapping();

        if (blank($mapping['sectionid'])) {
            throw new RuntimeException('The OSM waiting-list target section is incomplete.');
        }

        $syncDate = now()->toDateString();

        $payload = [
            'firstname' => $entry->first_name,
            'lastname' => $entry->last_name,
            'dob' => $entry->date_of_birth?->toDateString() ?? '',
            'startedsection' => $syncDate,
            'started' => $syncDate,
            'sectionid' => $mapping['sectionid'],
            'originating_section_id' => $mapping['sectionid'],
        ];

        if (filled($mapping['term_id'])) {
            $payload['term_id'] = $mapping['term_id'];
        }

        return $payload;
    }

    /**
     * @param  array<string, string>  $payload
     * @return array<string, mixed>
     */
    public function sync(WaitingListEntry $entry, array $payload): array
    {
        $connector = $this->connector();
        $authenticator = $this->authenticatorManager->resolveAuthenticator($connector);

        $response = $connector
            ->authenticate($authenticator)
            ->send(new CreateMemberRequest($payload));

        if ($response->status() === 401) {
            $authenticator = $this->authenticatorManager->forceRefreshAuthenticator($connector);

            $response = $connector
                ->authenticate($authenticator)
                ->send(new CreateMemberRequest($payload));
        }

        $this->guardResponse($response);

        if ($response->failed()) {
            throw new RuntimeException(
                'OSM member create request failed with HTTP '.$response->status().'. Context: '.json_encode($this->responseContext($response)),
            );
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        if (! is_array($data) || ($data['result'] ?? null) !== 'ok' || ! isset($data['scoutid'])) {
            throw new RuntimeException('OSM member create response did not contain the expected result payload.');
        }

        return [
            ...$data,
            '_meta' => $this->responseContext($response),
        ];
    }

    public function connector(): OsmConnector
    {
        return new OsmConnector;
    }

    private function guardResponse(Response $response): void
    {
        $blockedReason = $response->header('X-Blocked');

        if (is_string($blockedReason) && $blockedReason !== '') {
            throw new RuntimeException('OSM has blocked this application for the connected user: '.$blockedReason);
        }
    }

    /**
     * @return array<string, string>
     */
    private function responseContext(Response $response): array
    {
        return collect([
            'x_rate_limit_limit' => $response->header('X-RateLimit-Limit'),
            'x_rate_limit_remaining' => $response->header('X-RateLimit-Remaining'),
            'x_rate_limit_reset' => $response->header('X-RateLimit-Reset'),
            'retry_after' => $response->header('Retry-After'),
            'x_deprecated' => $response->header('X-Deprecated'),
            'x_blocked' => $response->header('X-Blocked'),
        ])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }
}
