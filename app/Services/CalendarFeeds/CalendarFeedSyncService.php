<?php

namespace App\Services\CalendarFeeds;

use App\Enums\CalendarFeedSyncStatus;
use App\Models\CalendarEvent;
use App\Models\CalendarFeedEventLink;
use App\Models\CalendarFeedSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class CalendarFeedSyncService
{
    public function __construct(
        private readonly IcsCalendarParser $parser,
    ) {}

    public function syncAll(): CalendarFeedSyncResult
    {
        return $this->syncSources(CalendarFeedSource::query()->enabled()->get());
    }

    public function syncSource(CalendarFeedSource $source): CalendarFeedSyncResult
    {
        return $this->syncSources(new EloquentCollection([$source]));
    }

    /**
     * @param  EloquentCollection<int, CalendarFeedSource>  $sources
     */
    private function syncSources(EloquentCollection $sources): CalendarFeedSyncResult
    {
        $startedAt = CarbonImmutable::now();
        $successfulSourceIds = [];
        $incomingEvents = collect();
        $failedSources = 0;

        foreach ($sources as $source) {
            if (! $source->is_enabled) {
                continue;
            }

            try {
                $syncPayload = $this->fetchAndParseSource($source);

                $successfulSourceIds[] = $source->getKey();
                $incomingEvents = $incomingEvents->concat($syncPayload['events']);

                $source->forceFill([
                    'last_synced_at' => $startedAt,
                    'last_sync_status' => CalendarFeedSyncStatus::SUCCESS,
                    'last_sync_error' => null,
                    'last_event_count' => $syncPayload['events']->count(),
                    'etag' => $syncPayload['etag'],
                    'last_modified' => $syncPayload['last_modified'],
                ])->save();
            } catch (Throwable $exception) {
                report($exception);

                $failedSources++;

                $source->forceFill([
                    'last_synced_at' => $startedAt,
                    'last_sync_status' => CalendarFeedSyncStatus::FAILED,
                    'last_sync_error' => mb_substr($exception->getMessage(), 0, 1000),
                ])->save();
            }
        }

        $eventsRemoved = 0;

        DB::transaction(function () use ($incomingEvents, $successfulSourceIds, $startedAt, &$eventsRemoved): void {
            $this->persistIncomingEvents($incomingEvents, $startedAt);
            $eventsRemoved = $this->deleteStaleLinks($successfulSourceIds, $incomingEvents);
            $this->reconcileSyncedEvents();
        });

        return new CalendarFeedSyncResult(
            sourcesProcessed: count($successfulSourceIds),
            sourcesFailed: $failedSources,
            eventsImported: $incomingEvents->count(),
            eventsRemoved: $eventsRemoved,
        );
    }

    /**
     * @return array{events: Collection<int, ParsedCalendarFeedEvent>, etag: ?string, last_modified: ?string}
     *
     * @throws ConnectionException
     */
    private function fetchAndParseSource(CalendarFeedSource $source): array
    {
        $response = Http::accept('text/calendar, text/plain;q=0.9, */*;q=0.8')
            ->connectTimeout(5)
            ->timeout(15)
            ->retry([200, 500], throw: false)
            ->get($source->feed_url);

        $response->throw();

        return [
            'events' => $this->parser
                ->parse($response->body(), $source->section, $source->getKey())
                ->map(fn (ParsedCalendarFeedEvent $event): ParsedCalendarFeedEvent => $this->sanitizeEvent($event))
                ->reject(fn (ParsedCalendarFeedEvent $event): bool => $this->shouldSkipEvent($event))
                ->values(),
            'etag' => $response->header('ETag'),
            'last_modified' => $response->header('Last-Modified'),
        ];
    }

    private function sanitizeEvent(ParsedCalendarFeedEvent $event): ParsedCalendarFeedEvent
    {
        $sanitizedContent = $this->sanitizeEventContent($event->content);

        if ($sanitizedContent === $event->content) {
            return $event;
        }

        return new ParsedCalendarFeedEvent(
            feedSourceId: $event->feedSourceId,
            section: $event->section,
            title: $event->title,
            startsAt: $event->startsAt,
            endsAt: $event->endsAt,
            allDay: $event->allDay,
            content: $sanitizedContent,
            externalEventKey: $event->externalEventKey,
            externalEventUid: $event->externalEventUid,
            mergeKey: $event->mergeKey,
            sourceFingerprint: $event->sourceFingerprint,
            payloadHash: $event->payloadHash,
        );
    }

    private function shouldSkipEvent(ParsedCalendarFeedEvent $event): bool
    {
        return $this->containsBirthdayMarker($event->title);
    }

    private function sanitizeEventContent(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $sanitizedLines = collect($lines)
            ->reject(fn (string $line): bool => $this->containsBirthdayMarker($line))
            ->values();

        $sanitizedContent = $sanitizedLines
            ->join("\n");

        $sanitizedContent = preg_replace("/\n{3,}/", "\n\n", trim($sanitizedContent));

        return blank($sanitizedContent) ? null : $sanitizedContent;
    }

    private function containsBirthdayMarker(string $value): bool
    {
        return Str::of($value)->lower()->test('/\bbirthdays?\b/');
    }

    /**
     * @param  Collection<int, ParsedCalendarFeedEvent>  $incomingEvents
     */
    private function persistIncomingEvents(Collection $incomingEvents, CarbonImmutable $startedAt): void
    {
        if ($incomingEvents->isEmpty()) {
            return;
        }

        $existingLinks = CalendarFeedEventLink::query()
            ->with(['calendarEvent' => fn ($query) => $query->withTrashed()])
            ->whereIn('calendar_feed_source_id', $incomingEvents->pluck('feedSourceId')->unique()->all())
            ->whereIn('external_event_key', $incomingEvents->pluck('externalEventKey')->unique()->all())
            ->get()
            ->keyBy(fn (CalendarFeedEventLink $link): string => $this->linkMapKey(
                $link->calendar_feed_source_id,
                $link->external_event_key,
            ));

        $existingEventsByMergeKey = CalendarEvent::query()
            ->withTrashed()
            ->where('is_manual', false)
            ->whereIn('sync_merge_key', $incomingEvents->pluck('mergeKey')->unique()->all())
            ->get()
            ->keyBy('sync_merge_key');

        foreach ($incomingEvents->groupBy('mergeKey') as $mergeKey => $eventGroup) {
            $primaryEvent = $this->resolvePrimaryEvent($eventGroup, $existingLinks, $existingEventsByMergeKey);

            $primaryEvent->forceFill($this->buildEventAttributes($eventGroup, $mergeKey))->save();

            if ($primaryEvent->trashed()) {
                $primaryEvent->restore();
            }

            foreach ($eventGroup as $event) {
                CalendarFeedEventLink::query()->updateOrCreate(
                    [
                        'calendar_feed_source_id' => $event->feedSourceId,
                        'external_event_key' => $event->externalEventKey,
                    ],
                    [
                        'calendar_event_id' => $primaryEvent->getKey(),
                        'external_event_uid' => $event->externalEventUid,
                        'merge_key' => $mergeKey,
                        'source_fingerprint' => $event->sourceFingerprint,
                        'payload_hash' => $event->payloadHash,
                        'last_seen_at' => $startedAt,
                    ],
                );
            }
        }
    }

    /**
     * @param  Collection<int, ParsedCalendarFeedEvent>  $eventGroup
     * @param  Collection<string, CalendarFeedEventLink>  $existingLinks
     * @param  Collection<string, CalendarEvent>  $existingEventsByMergeKey
     */
    private function resolvePrimaryEvent(
        Collection $eventGroup,
        Collection $existingLinks,
        Collection $existingEventsByMergeKey,
    ): CalendarEvent {
        $existingEvents = $eventGroup
            ->map(function (ParsedCalendarFeedEvent $event) use ($existingLinks): ?CalendarEvent {
                $link = $existingLinks->get($this->linkMapKey($event->feedSourceId, $event->externalEventKey));

                return $link?->calendarEvent;
            })
            ->filter()
            ->unique(fn (CalendarEvent $event): int => $event->getKey())
            ->values();

        /** @var CalendarEvent $primaryEvent */
        $primaryEvent = $existingEvents->first()
            ?? $existingEventsByMergeKey->get($eventGroup->first()->mergeKey)
            ?? new CalendarEvent;

        $duplicates = $existingEvents
            ->reject(fn (CalendarEvent $event): bool => $event->is($primaryEvent))
            ->values();

        if ($duplicates->isNotEmpty()) {
            CalendarFeedEventLink::query()
                ->whereIn('calendar_event_id', $duplicates->pluck('id')->all())
                ->update(['calendar_event_id' => $primaryEvent->getKey()]);

            CalendarEvent::query()
                ->whereIn('id', $duplicates->pluck('id')->all())
                ->get()
                ->each
                ->delete();
        }

        return $primaryEvent;
    }

    /**
     * @param  Collection<int, ParsedCalendarFeedEvent>  $eventGroup
     * @return array<string, mixed>
     */
    private function buildEventAttributes(Collection $eventGroup, string $mergeKey): array
    {
        /** @var ParsedCalendarFeedEvent $primary */
        $primary = $eventGroup
            ->sortBy(fn (ParsedCalendarFeedEvent $event): string => sprintf('%s-%s', $event->feedSourceId, $event->externalEventKey))
            ->first();

        return [
            'title' => $primary->title,
            'starts_at' => $eventGroup->min(fn (ParsedCalendarFeedEvent $event): CarbonImmutable => $event->startsAt),
            'ends_at' => $eventGroup->max(fn (ParsedCalendarFeedEvent $event): CarbonImmutable => $event->endsAt),
            'all_day' => $primary->allDay,
            'is_manual' => false,
            'sync_merge_key' => $mergeKey,
            'content' => $eventGroup
                ->pluck('content')
                ->filter()
                ->sortByDesc(fn (?string $content): int => mb_strlen($content ?? ''))
                ->first(),
            'sections' => $eventGroup
                ->pluck('section')
                ->unique(fn ($section) => $section->value)
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  list<int>  $successfulSourceIds
     * @param  Collection<int, ParsedCalendarFeedEvent>  $incomingEvents
     */
    private function deleteStaleLinks(array $successfulSourceIds, Collection $incomingEvents): int
    {
        if ($successfulSourceIds === []) {
            return 0;
        }

        $staleLinks = collect();

        foreach ($successfulSourceIds as $sourceId) {
            $seenKeys = $incomingEvents
                ->where('feedSourceId', $sourceId)
                ->pluck('externalEventKey')
                ->unique()
                ->values()
                ->all();

            $query = CalendarFeedEventLink::query()
                ->where('calendar_feed_source_id', $sourceId);

            if ($seenKeys !== []) {
                $query->whereNotIn('external_event_key', $seenKeys);
            }

            $staleLinks = $staleLinks->concat($query->get());
        }

        if ($staleLinks->isEmpty()) {
            return 0;
        }

        CalendarFeedEventLink::query()
            ->whereIn('id', $staleLinks->pluck('id')->all())
            ->delete();

        return $staleLinks->count();
    }

    private function reconcileSyncedEvents(): void
    {
        CalendarEvent::query()
            ->withTrashed()
            ->where('is_manual', false)
            ->with('feedEventLinks.feedSource')
            ->get()
            ->each(function (CalendarEvent $event): void {
                $sections = $event->feedEventLinks
                    ->map(fn (CalendarFeedEventLink $link) => $link->feedSource?->section)
                    ->filter()
                    ->unique(fn ($section) => $section->value)
                    ->values();

                if ($sections->isEmpty()) {
                    if (! $event->trashed()) {
                        $event->delete();
                    }

                    return;
                }

                $event->forceFill([
                    'sections' => $sections->all(),
                ])->save();

                if ($event->trashed()) {
                    $event->restore();
                }
            });
    }

    private function linkMapKey(int $feedSourceId, string $externalEventKey): string
    {
        return sprintf('%s:%s', $feedSourceId, $externalEventKey);
    }
}
