<?php

use App\Http\Integrations\Osm\Requests\ListSectionsRequest;
use App\Http\Integrations\Osm\Requests\ListTermsRequest;
use App\Jobs\RefreshOsmDirectorySnapshot;
use App\Services\WaitingList\Osm\OsmDirectoryService;
use App\Settings\OsmSettings;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\OAuth2\GetUserRequest;
use Saloon\Laravel\Facades\Saloon;
use Saloon\RateLimitPlugin\Helpers\ApiRateLimited;

beforeEach(function () {
    config()->set('services.osm.client_id', 'client-id');
    config()->set('services.osm.client_secret', 'client-secret');
    config()->set('services.osm.redirect_uri', 'https://scout-group-website.test/admin/osm/callback');
    config()->set('services.osm.scopes', ['section:member:write']);
});

it('stores a directory snapshot from OSM in a queued job', function () {
    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'access-token';
    $settings->access_token_expires_at = now()->addHour()->toIso8601String();
    $settings->markDirectoryRefreshQueued();

    Saloon::fake([
        GetUserRequest::class => MockResponse::make([
            'name' => 'Pat Leader',
            'email' => 'pat@example.com',
            'sections' => [
                ['sectionid' => '101', 'name' => 'Example Group Squirrels'],
            ],
        ]),
        ListSectionsRequest::class => MockResponse::make([
            'items' => [
                ['sectionid' => '101', 'name' => 'Example Group Squirrels'],
            ],
        ]),
        ListTermsRequest::class => MockResponse::make([
            '101' => [
                [
                    'termid' => '201',
                    'sectionid' => '101',
                    'name' => 'Current Squirrels',
                    'startdate' => '2026-01-01',
                    'enddate' => '2026-04-01',
                ],
            ],
        ]),
    ]);

    app(RefreshOsmDirectorySnapshot::class)->handle(
        app(OsmDirectoryService::class),
        $settings,
    );

    $settings->refresh();

    expect($settings->directory_account_name)->toBe('Pat Leader')
        ->and($settings->directory_account_email)->toBe('pat@example.com')
        ->and($settings->directorySections())->toBe([
            '101' => 'Example Group Squirrels',
        ])
        ->and($settings->directoryTermsBySection())->toBe([
            '101' => [
                '201' => 'Current Squirrels (2026-01-01 to 2026-04-01)',
            ],
        ])
        ->and($settings->directory_refreshed_at)->not->toBeNull()
        ->and($settings->directory_refresh_queued_at)->toBeNull()
        ->and($settings->directory_last_error)->toBeNull();
});

it('uses the saloon rate-limit middleware on the directory refresh job', function () {
    expect((new RefreshOsmDirectorySnapshot)->middleware())
        ->toHaveCount(1)
        ->and((new RefreshOsmDirectorySnapshot)->middleware()[0])
        ->toBeInstanceOf(ApiRateLimited::class);
});
