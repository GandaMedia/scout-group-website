<?php

use App\Enums\CalendarFeedSyncStatus;
use App\Enums\Section;
use App\Models\CalendarEvent;
use App\Models\CalendarFeedEventLink;
use App\Models\CalendarFeedSource;
use App\Services\CalendarFeeds\CalendarFeedSyncService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;

test('it imports enabled OSM feeds into synced calendar events', function () {
    $source = CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(icsFeed([
            icsEvent(
                uid: 'scouts-camp-1',
                title: 'Summer Camp',
                startsAt: '20260710T180000',
                endsAt: '20260712T110000',
                description: 'Bring a sleeping bag',
            ),
        ]), 200, ['Content-Type' => 'text/calendar']),
    ]);

    $result = app(CalendarFeedSyncService::class)->syncAll();

    expect($result->sourcesProcessed)->toBe(1)
        ->and($result->sourcesFailed)->toBe(0)
        ->and($result->eventsImported)->toBe(1);

    $event = CalendarEvent::query()->sole();

    expect($event->is_manual)->toBeFalse()
        ->and($event->title)->toBe('Summer Camp')
        ->and($event->content)->toBe('Bring a sleeping bag')
        ->and($event->sections)->toBe([Section::SCOUTS]);

    expect(CalendarFeedEventLink::query()->whereBelongsTo($source, 'feedSource')->count())->toBe(1);

    $source->refresh();

    expect($source->last_sync_status)->toBe(CalendarFeedSyncStatus::SUCCESS)
        ->and($source->last_event_count)->toBe(1)
        ->and($source->last_sync_error)->toBeNull();
});

test('it is idempotent across repeated syncs', function () {
    CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(icsFeed([
            icsEvent(
                uid: 'district-night',
                title: 'District Night',
                startsAt: '20260715T190000',
                endsAt: '20260715T210000',
            ),
        ]), 200, ['Content-Type' => 'text/calendar']),
    ]);

    $service = app(CalendarFeedSyncService::class);

    $service->syncAll();
    $service->syncAll();

    expect(CalendarEvent::query()->count())->toBe(1)
        ->and(CalendarFeedEventLink::query()->count())->toBe(1);
});

test('it updates existing synced events when the source feed changes', function () {
    CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    $service = app(CalendarFeedSyncService::class);

    Http::fake([
        'https://example.test/scouts.ics' => Http::sequence()
            ->push(icsFeed([
                icsEvent(
                    uid: 'planning-night',
                    title: 'Planning Night',
                    startsAt: '20260720T190000',
                    endsAt: '20260720T203000',
                    description: 'Original agenda',
                ),
            ]), 200)
            ->push(icsFeed([
                icsEvent(
                    uid: 'planning-night',
                    title: 'Planning Night Updated',
                    startsAt: '20260720T193000',
                    endsAt: '20260720T210000',
                    description: 'Updated agenda',
                ),
            ]), 200),
    ]);

    $service->syncAll();
    $service->syncAll();

    $event = CalendarEvent::query()->sole();

    expect($event->title)->toBe('Planning Night Updated')
        ->and($event->content)->toBe('Updated agenda')
        ->and($event->starts_at->format('Y-m-d H:i:s'))->toBe('2026-07-20 19:30:00')
        ->and($event->ends_at->format('Y-m-d H:i:s'))->toBe('2026-07-20 21:00:00');
});

test('it soft deletes synced events that disappear from a successful feed sync', function () {
    CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    $service = app(CalendarFeedSyncService::class);

    Http::fake([
        'https://example.test/scouts.ics' => Http::sequence()
            ->push(icsFeed([
                icsEvent(
                    uid: 'campfire',
                    title: 'Campfire',
                    startsAt: '20260721T180000',
                    endsAt: '20260721T200000',
                ),
            ]), 200)
            ->push(icsFeed([]), 200),
    ]);

    $service->syncAll();
    $service->syncAll();

    expect(CalendarEvent::query()->count())->toBe(0)
        ->and(CalendarEvent::query()->withTrashed()->count())->toBe(1)
        ->and(CalendarFeedEventLink::query()->count())->toBe(0);
});

test('it merges duplicate real-world events across section feeds', function () {
    CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    CalendarFeedSource::factory()->create([
        'section' => Section::CUBS,
        'feed_url' => 'https://example.test/cubs.ics',
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(icsFeed([
            icsEvent(
                uid: 'scouts-123',
                title: 'Group Camp',
                startsAt: '20260730T170000',
                endsAt: '20260801T120000',
            ),
        ]), 200),
        'https://example.test/cubs.ics' => Http::response(icsFeed([
            icsEvent(
                uid: 'cubs-456',
                title: 'Group Camp',
                startsAt: '20260730T170000',
                endsAt: '20260801T120000',
            ),
        ]), 200),
    ]);

    app(CalendarFeedSyncService::class)->syncAll();

    $event = CalendarEvent::query()->sole();

    expect($event->sections)->toBe([Section::SCOUTS, Section::CUBS])
        ->and(CalendarFeedEventLink::query()->count())->toBe(2);
});

test('it never overwrites manual site events during sync', function () {
    CalendarEvent::factory()->create([
        'title' => 'Group Camp',
        'starts_at' => '2026-07-30 17:00:00',
        'ends_at' => '2026-08-01 12:00:00',
        'content' => 'Manual note',
        'all_day' => false,
        'is_manual' => true,
        'sections' => [Section::SCOUTS],
    ]);

    CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(icsFeed([
            icsEvent(
                uid: 'sync-group-camp',
                title: 'Group Camp',
                startsAt: '20260730T170000',
                endsAt: '20260801T120000',
                description: 'Synced note',
            ),
        ]), 200),
    ]);

    app(CalendarFeedSyncService::class)->syncAll();

    expect(CalendarEvent::query()->count())->toBe(2)
        ->and(CalendarEvent::query()->where('is_manual', true)->sole()->content)->toBe('Manual note')
        ->and(CalendarEvent::query()->where('is_manual', false)->sole()->content)->toBe('Synced note');
});

test('it does not import birthday events from OSM feeds', function () {
    $source = CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(icsFeed([
            icsEvent(
                uid: 'birthday-1',
                title: 'Birthday: Young Person',
                startsAt: '20260801T000000',
                endsAt: '20260801T235900',
            ),
            icsEvent(
                uid: 'camp-night',
                title: 'Camp Night',
                startsAt: '20260801T190000',
                endsAt: '20260801T210000',
            ),
        ]), 200),
    ]);

    $result = app(CalendarFeedSyncService::class)->syncAll();

    expect($result->eventsImported)->toBe(1)
        ->and(CalendarEvent::query()->count())->toBe(1)
        ->and(CalendarEvent::query()->sole()->title)->toBe('Camp Night')
        ->and(CalendarFeedEventLink::query()->count())->toBe(1);

    $source->refresh();

    expect($source->last_event_count)->toBe(1);
});

test('it strips birthday lines from synced event descriptions', function () {
    CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(icsFeed([
            icsEvent(
                uid: 'invention-night',
                title: 'Invention Night',
                startsAt: '20260701T191500',
                endsAt: '20260701T204500',
                description: "Build challenge\nBirthday: Alex Example",
            ),
        ]), 200),
    ]);

    app(CalendarFeedSyncService::class)->syncAll();

    $event = CalendarEvent::query()->sole();

    expect($event->title)->toBe('Invention Night')
        ->and($event->content)->toBe('Build challenge');
});

test('it removes previously synced birthday events on the next successful sync', function () {
    $source = CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    $birthdayEvent = CalendarEvent::factory()->create([
        'title' => 'Birthday: Young Person',
        'starts_at' => '2026-08-01 00:00:00',
        'ends_at' => '2026-08-01 23:59:00',
        'all_day' => false,
        'is_manual' => false,
        'sync_merge_key' => 'birthday-merge-key',
        'sections' => [Section::SCOUTS],
    ]);

    CalendarFeedEventLink::query()->create([
        'calendar_event_id' => $birthdayEvent->getKey(),
        'calendar_feed_source_id' => $source->getKey(),
        'external_event_key' => hash('sha256', 'birthday-uid'),
        'external_event_uid' => 'birthday-uid',
        'merge_key' => 'birthday-merge-key',
        'source_fingerprint' => 'birthday-fingerprint',
        'payload_hash' => 'birthday-payload-hash',
        'last_seen_at' => now(),
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(icsFeed([
            icsEvent(
                uid: 'birthday-uid',
                title: 'Birthday: Young Person',
                startsAt: '20260801T000000',
                endsAt: '20260801T235900',
            ),
        ]), 200),
    ]);

    $result = app(CalendarFeedSyncService::class)->syncAll();

    expect($result->eventsImported)->toBe(0)
        ->and($result->eventsRemoved)->toBe(1)
        ->and(CalendarFeedEventLink::query()->count())->toBe(0)
        ->and(CalendarEvent::query()->count())->toBe(0)
        ->and(CalendarEvent::query()->withTrashed()->count())->toBe(1);
});

test('it records sync failures without corrupting existing events', function () {
    $source = CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::sequence()
            ->push(icsFeed([
                icsEvent(
                    uid: 'lakeside',
                    title: 'Lakeside Hike',
                    startsAt: '20260802T090000',
                    endsAt: '20260802T130000',
                ),
            ]), 200)
            ->push('not a calendar', 200),
    ]);

    $service = app(CalendarFeedSyncService::class);
    $service->syncAll();
    $service->syncAll();

    $source->refresh();

    expect($source->last_sync_status)->toBe(CalendarFeedSyncStatus::FAILED)
        ->and(CalendarEvent::query()->count())->toBe(1)
        ->and(CalendarFeedEventLink::query()->count())->toBe(1);
});

test('the scheduled midnight sync command is registered', function () {
    $scheduledEvent = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains($event->command, 'calendar:sync-osm-feeds'));

    expect($scheduledEvent)->not->toBeNull()
        ->and($scheduledEvent->expression)->toBe('0 0 * * *');
});

test('the sync command runs the feed sync service', function () {
    CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(icsFeed([
            icsEvent(
                uid: 'command-event',
                title: 'Command Event',
                startsAt: '20260810T190000',
                endsAt: '20260810T210000',
            ),
        ]), 200),
    ]);

    $this->artisan('calendar:sync-osm-feeds')
        ->expectsOutputToContain('Processed 1 feed')
        ->assertSuccessful();

    expect(CalendarEvent::query()->where('title', 'Command Event')->exists())->toBeTrue();
});

function icsFeed(array $events): string
{
    return implode("\r\n", [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Example Scout Group//Calendar//EN',
        ...$events,
        'END:VCALENDAR',
        '',
    ]);
}

function icsEvent(
    string $uid,
    string $title,
    string $startsAt,
    string $endsAt,
    ?string $description = null,
): string {
    $lines = [
        'BEGIN:VEVENT',
        'UID:'.$uid,
        'SUMMARY:'.$title,
        'DTSTART;TZID=Europe/London:'.$startsAt,
        'DTEND;TZID=Europe/London:'.$endsAt,
    ];

    if ($description !== null) {
        $lines[] = 'DESCRIPTION:'.$description;
    }

    $lines[] = 'END:VEVENT';

    return implode("\r\n", $lines);
}
