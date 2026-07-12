<?php

use App\Enums\WaitingListEntrySyncStatus;
use App\Http\Integrations\Osm\Requests\CreateMemberRequest;
use App\Jobs\SyncWaitingListEntry;
use App\Models\WaitingListEntry;
use App\Services\WaitingList\OsmWaitingListService;
use App\Settings\OsmSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Saloon\Laravel\Facades\Saloon;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;
use Saloon\RateLimitPlugin\Helpers\ApiRateLimited;

it('syncs an eligible waiting-list entry using the expected OSM payload', function () {
    Carbon::setTestNow('2026-07-01 10:15:00');

    configureOsmSettings();

    $entry = WaitingListEntry::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'date_of_birth' => '2018-03-14',
        'section_slug' => 'beavers',
    ]);

    Saloon::fake([
        CreateMemberRequest::class => MockResponse::make([
            'result' => 'ok',
            'out_of_term' => false,
            'scoutid' => 3100005,
        ], 200, [
            'X-RateLimit-Limit' => '500',
            'X-RateLimit-Remaining' => '499',
            'X-RateLimit-Reset' => '3500',
        ]),
    ]);

    app(SyncWaitingListEntry::class, ['waitingListEntryId' => $entry->id])
        ->handle(app(OsmWaitingListService::class));

    $entry->refresh();

    expect($entry->sync_status)->toBe(WaitingListEntrySyncStatus::SYNCED)
        ->and($entry->osm_scout_id)->toBe(3100005)
        ->and($entry->synced_at?->toDateTimeString())->toBe('2026-07-01 10:15:00')
        ->and($entry->last_payload)->toBe([
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'dob' => '2018-03-14',
            'startedsection' => '2026-07-01',
            'started' => '2026-07-01',
            'sectionid' => '901',
            'originating_section_id' => '901',
            'term_id' => '902',
        ])
        ->and($entry->osm_response)->toMatchArray([
            'result' => 'ok',
            'out_of_term' => false,
            'scoutid' => 3100005,
            '_meta' => [
                'x_rate_limit_limit' => '500',
                'x_rate_limit_remaining' => '499',
                'x_rate_limit_reset' => '3500',
            ],
        ]);

    Saloon::assertSent(function (CreateMemberRequest $request, Response $response): bool {
        $pendingRequest = $response->getPendingRequest();

        return $pendingRequest->getUrl() === 'https://www.onlinescoutmanager.co.uk/ext/members/contact/actions/?action=newMember'
            && $response->getPsrRequest()->getHeaderLine('Authorization') === 'Bearer existing-access-token'
            && $pendingRequest->body()?->all() === [
                'firstname' => 'Ada',
                'lastname' => 'Lovelace',
                'dob' => '2018-03-14',
                'startedsection' => '2026-07-01',
                'started' => '2026-07-01',
                'sectionid' => '901',
                'originating_section_id' => '901',
                'term_id' => '902',
            ];
    });
});

it('marks a waiting-list entry as failed when OSM rejects the request', function () {
    configureOsmSettings();

    $entry = WaitingListEntry::factory()->create([
        'section_slug' => 'beavers',
    ]);

    Saloon::fake([
        CreateMemberRequest::class => MockResponse::make([
            'error' => 'nope',
        ], 500),
    ]);

    app(SyncWaitingListEntry::class, ['waitingListEntryId' => $entry->id])
        ->handle(app(OsmWaitingListService::class));

    $entry->refresh();

    expect($entry->sync_status)->toBe(WaitingListEntrySyncStatus::FAILED)
        ->and($entry->last_error)->toContain('HTTP 500')
        ->and($entry->last_error_at)->not->toBeNull()
        ->and($entry->synced_at)->toBeNull();
});

it('syncs an eligible waiting-list entry without a term when the target section has none', function () {
    Carbon::setTestNow('2026-07-01 10:15:00');

    configureOsmSettings(termId: '');

    $entry = WaitingListEntry::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'date_of_birth' => '2018-03-14',
        'section_slug' => 'beavers',
    ]);

    Saloon::fake([
        CreateMemberRequest::class => MockResponse::make([
            'result' => 'ok',
            'out_of_term' => false,
            'scoutid' => 3100005,
        ], 200),
    ]);

    app(SyncWaitingListEntry::class, ['waitingListEntryId' => $entry->id])
        ->handle(app(OsmWaitingListService::class));

    $entry->refresh();

    expect($entry->last_payload)->toBe([
        'firstname' => 'Ada',
        'lastname' => 'Lovelace',
        'dob' => '2018-03-14',
        'startedsection' => '2026-07-01',
        'started' => '2026-07-01',
        'sectionid' => '901',
        'originating_section_id' => '901',
    ]);

    Saloon::assertSent(function (CreateMemberRequest $request, Response $response): bool {
        return $response->getPendingRequest()->body()?->all() === [
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'dob' => '2018-03-14',
            'startedsection' => '2026-07-01',
            'started' => '2026-07-01',
            'sectionid' => '901',
            'originating_section_id' => '901',
        ];
    });
});

it('requeues the entry when OSM rate limits the request', function () {
    configureOsmSettings();

    $entry = WaitingListEntry::factory()->create([
        'section_slug' => 'beavers',
    ]);

    Saloon::fake([
        CreateMemberRequest::class => MockResponse::make([
            'error' => 'too-many-requests',
        ], 429, [
            'Retry-After' => '120',
        ]),
    ]);

    expect(fn () => app(SyncWaitingListEntry::class, ['waitingListEntryId' => $entry->id])
        ->handle(app(OsmWaitingListService::class)))
        ->toThrow(RateLimitReachedException::class);

    $entry->refresh();

    expect($entry->sync_status)->toBe(WaitingListEntrySyncStatus::PENDING)
        ->and($entry->last_error)->toBeNull()
        ->and($entry->sync_queued_at)->not->toBeNull();
});

it('queues only eligible pending entries during the scheduled sync run', function () {
    Queue::fake();

    $pending = WaitingListEntry::factory()->create([
        'sync_status' => WaitingListEntrySyncStatus::PENDING,
    ]);
    $held = WaitingListEntry::factory()->heldDuplicate()->create();
    $synced = WaitingListEntry::factory()->synced()->create();

    $this->artisan('waiting-list:sync')
        ->assertSuccessful();

    Queue::assertPushed(SyncWaitingListEntry::class, function (SyncWaitingListEntry $job) use ($pending): bool {
        return $job->waitingListEntryId === $pending->id;
    });
    Queue::assertPushed(SyncWaitingListEntry::class, 1);

    expect($pending->fresh()->sync_queued_at)->not->toBeNull()
        ->and($held->fresh()->sync_status)->toBe(WaitingListEntrySyncStatus::HELD_DUPLICATE)
        ->and($synced->fresh()->sync_status)->toBe(WaitingListEntrySyncStatus::SYNCED);
});

it('uses the saloon rate-limit middleware on the sync job', function () {
    expect((new SyncWaitingListEntry(123))->middleware())
        ->toHaveCount(1)
        ->and((new SyncWaitingListEntry(123))->middleware()[0])
        ->toBeInstanceOf(ApiRateLimited::class);
});

function configureOsmSettings(string $termId = '902'): void
{
    config()->set('services.osm.client_id', 'client-id');
    config()->set('services.osm.client_secret', 'client-secret');
    config()->set('services.osm.redirect_uri', 'https://scout-group-website.test/admin/osm/callback');
    config()->set('services.osm.scopes', ['section:member:write']);

    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'existing-access-token';
    $settings->access_token_expires_at = '2099-01-01T00:00:00+00:00';
    $settings->target_section_id = '901';
    $settings->target_term_id = $termId;
    $settings->save();
}
