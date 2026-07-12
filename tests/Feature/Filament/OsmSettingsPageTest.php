<?php

use App\Enums\Section;
use App\Filament\Pages\OsmSettings as OsmSettingsPage;
use App\Http\Integrations\Osm\Requests\GetMemberPhotoRequest;
use App\Http\Integrations\Osm\Requests\ListMembersRequest;
use App\Jobs\ImportOsmLeaderPhoto;
use App\Jobs\ImportOsmSectionLeaders as ImportOsmSectionLeadersJob;
use App\Jobs\RefreshOsmDirectorySnapshot;
use App\Models\Leader;
use App\Models\LeaderSection;
use App\Models\User;
use App\Services\Leaders\ImportOsmSectionLeaders as ImportOsmSectionLeadersService;
use App\Settings\OsmSettings;
use App\Settings\SectionSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

beforeEach(function () {
    Carbon::setTestNow('2026-07-01 12:00:00');

    app()->forgetInstance(OsmSettings::class);
    app()->forgetInstance(SectionSettings::class);

    $sectionSettings = app(SectionSettings::class);
    $sectionSettings->enabled_section_slugs = Section::slugs();
    $sectionSettings->save();

    config()->set('services.osm.client_id', 'client-id');
    config()->set('services.osm.client_secret', 'client-secret');
    config()->set('services.osm.redirect_uri', 'https://scout-group-website.test/admin/osm/callback');
    config()->set('services.osm.scopes', ['section:member:write']);

    $this->actingAs(User::factory()->create());
});

it('allows admins to update the group-wide OSM waiting-list target', function () {
    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'access-token';
    $settings->access_token_expires_at = now()->addYears(10)->toIso8601String();
    $settings->storeDirectorySnapshot(
        accountName: 'Pat Leader',
        accountEmail: 'pat@example.com',
        sections: [
            '101' => 'Example Group Squirrels',
            '301' => 'Example Group Beavers',
            '501' => 'Example Group Cubs',
            '701' => 'Example Group Scouts',
            '901' => 'Example Group Explorers',
            '1101' => 'Example Group Network',
        ],
        termsBySection: [
            '101' => ['201' => 'Current Squirrels'],
            '301' => ['401' => 'Current Beavers'],
            '501' => ['601' => 'Current Cubs'],
            '701' => ['801' => 'Current Scouts'],
            '901' => ['1001' => 'Current Explorers'],
            '1101' => ['1201' => 'Current Network'],
        ],
    );

    Livewire::test(OsmSettingsPage::class)
        ->assertSee('Connected')
        ->assertSee('Pat Leader - pat@example.com')
        ->assertFormFieldExists('explorers_section_id')
        ->assertFormFieldExists('network_section_id')
        ->fillForm([
            'squirrels_section_id' => '101',
            'beavers_section_id' => '301',
            'cubs_section_id' => '501',
            'scouts_section_id' => '701',
            'explorers_section_id' => '901',
            'network_section_id' => '1101',
            'target_section_id' => '301',
            'target_term_id' => '401',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings->refresh();

    expect($settings->squirrels_section_id)->toBe('101')
        ->and($settings->beavers_section_id)->toBe('301')
        ->and($settings->cubs_section_id)->toBe('501')
        ->and($settings->scouts_section_id)->toBe('701')
        ->and($settings->explorers_section_id)->toBe('901')
        ->and($settings->network_section_id)->toBe('1101')
        ->and($settings->target_section_id)->toBe('301')
        ->and($settings->target_term_id)->toBe('401');
});

it('only shows active website sections in the public OSM section source mappings', function () {
    $sectionSettings = app(SectionSettings::class);
    $sectionSettings->enabled_section_slugs = [
        Section::SQUIRRELS->slug(),
        Section::BEAVERS->slug(),
        Section::CUBS->slug(),
        Section::SCOUTS->slug(),
    ];
    $sectionSettings->save();

    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'access-token';
    $settings->access_token_expires_at = now()->addYears(10)->toIso8601String();
    $settings->storeDirectorySnapshot(
        accountName: 'Pat Leader',
        accountEmail: 'pat@example.com',
        sections: [
            '901' => 'Example Group Explorers',
            '1101' => 'Example Group Network',
        ],
        termsBySection: [],
    );
    $settings->explorers_section_id = '901';
    $settings->network_section_id = '1101';
    $settings->save();

    Livewire::test(OsmSettingsPage::class)
        ->assertFormFieldExists('squirrels_section_id')
        ->assertFormFieldDoesNotExist('explorers_section_id')
        ->assertFormFieldDoesNotExist('network_section_id');

    expect(app(OsmSettings::class)->publicSectionMappings())
        ->not->toHaveKeys([
            Section::EXPLORERS->value,
            Section::NETWORK->value,
        ]);
});

it('queues a directory refresh instead of calling OSM inline', function () {
    Queue::fake();

    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'access-token';
    $settings->access_token_expires_at = now()->addYears(10)->toIso8601String();
    $settings->save();

    Livewire::test(OsmSettingsPage::class)
        ->call('refreshDirectoryData')
        ->assertNotified();

    Queue::assertPushed(RefreshOsmDirectorySnapshot::class);

    expect(app(OsmSettings::class)->directory_refresh_queued_at)->not->toBeNull();
});

it('allows admins to save a waiting-list section without a term when OSM exposes no terms for it', function () {
    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'access-token';
    $settings->access_token_expires_at = now()->addYears(10)->toIso8601String();
    $settings->storeDirectorySnapshot(
        accountName: 'Pat Leader',
        accountEmail: 'pat@example.com',
        sections: [
            '67254' => 'Example Group: Waiting List',
        ],
        termsBySection: [
            '301' => [
                '401' => 'Spring 2026 (2026-01-01 to 2026-04-12)',
                '402' => 'Summer 2026 (2026-04-13 to 2026-08-31)',
            ],
        ],
    );

    Livewire::test(OsmSettingsPage::class)
        ->fillForm([
            'target_section_id' => '67254',
            'target_term_id' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings->refresh();

    expect($settings->target_section_id)->toBe('67254')
        ->and($settings->target_term_id)->toBe('');
});

it('queues the mapped OSM section leader import only when the action is clicked', function () {
    Queue::fake();

    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'existing-access-token';
    $settings->access_token_expires_at = now()->addYears(10)->toIso8601String();
    $settings->storeDirectorySnapshot(
        accountName: 'Pat Leader',
        accountEmail: 'pat@example.com',
        sections: [
            '301' => 'Example Group Beaver Colony',
        ],
        termsBySection: [
            '301' => [
                '401' => 'Spring 2026 (2026-01-01 to 2026-04-12)',
                '402' => 'Summer 2026 (2026-04-13 to 2026-08-31)',
            ],
        ],
    );
    $settings->beavers_section_id = '301';
    $settings->save();

    Livewire::test(OsmSettingsPage::class)
        ->assertActionExists('seedLeaders')
        ->call('seedLeaders')
        ->assertNotified();

    Queue::assertPushed(ImportOsmSectionLeadersJob::class);
    expect(Leader::query()->where('name', 'Pat Leader')->exists())->toBeFalse();
});

it('imports missing leaders from mapped OSM section member lists when the queued job runs', function () {
    Queue::fake([
        ImportOsmLeaderPhoto::class,
    ]);

    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'existing-access-token';
    $settings->access_token_expires_at = now()->addYears(10)->toIso8601String();
    $settings->storeDirectorySnapshot(
        accountName: 'Pat Leader',
        accountEmail: 'pat@example.com',
        sections: [
            '301' => 'Example Group Beaver Colony',
        ],
        termsBySection: [
            '301' => [
                '402' => 'Summer 2026 (2026-04-13 to 2026-08-31)',
            ],
        ],
    );
    $settings->beavers_section_id = '301';
    $settings->save();

    Saloon::fake([
        ListMembersRequest::class => MockResponse::make([
            'items' => [
                [
                    'firstname' => 'Ada',
                    'lastname' => 'Young Member',
                    'patrolid' => '12',
                    'patrol' => 'Red Lodge',
                ],
                [
                    'firstname' => 'Pat',
                    'lastname' => 'Leader',
                    'photo_guid' => '11111111-2222-3333-4444-555555555555',
                    'patrolid' => '-2',
                    'patrol' => 'Leaders',
                    'scoutid' => 3100001,
                ],
            ],
        ]),
    ]);

    expect(Leader::query()->where('name', 'Pat Leader')->exists())->toBeFalse();

    expect($settings->publicSectionMappings())->toMatchArray([
        Section::BEAVERS->value => '301',
    ]);

    app(ImportOsmSectionLeadersJob::class)->handle(
        app(ImportOsmSectionLeadersService::class),
        $settings,
    );

    $leader = Leader::query()->with('sectionAssignments')->where('name', 'Pat Leader')->sole();

    expect($leader->is_active)->toBeFalse()
        ->and($leader->bio)->toContain('imported from OSM')
        ->and($leader->hasMedia('photo'))->toBeFalse()
        ->and($leader->sectionAssignments)->toHaveCount(1)
        ->and($leader->sectionAssignments->pluck('section')->all())->toEqualCanonicalizing([
            Section::BEAVERS,
        ]);

    expect(Leader::query()->where('name', 'Ada Young Member')->exists())->toBeFalse();

    Saloon::assertSent(ListMembersRequest::class);
    Queue::assertPushed(ImportOsmLeaderPhoto::class, function (ImportOsmLeaderPhoto $job) use ($leader): bool {
        return $job->leaderId === $leader->id
            && $job->scoutId === '3100001'
            && $job->photoGuid === '11111111-2222-3333-4444-555555555555';
    });
});

it('does not import or assign OSM leaders that already exist locally', function () {
    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'existing-access-token';
    $settings->access_token_expires_at = now()->addYears(10)->toIso8601String();
    $settings->storeDirectorySnapshot(
        accountName: 'Pat Leader',
        accountEmail: 'pat@example.com',
        sections: [
            '301' => 'Example Group Beaver Colony',
        ],
        termsBySection: [
            '301' => [
                '402' => 'Summer 2026 (2026-04-13 to 2026-08-31)',
            ],
        ],
    );
    $settings->beavers_section_id = '301';
    $settings->save();

    Saloon::fake([
        ListMembersRequest::class => MockResponse::make([
            'items' => [
                [
                    'firstname' => 'Pat',
                    'lastname' => 'Leader',
                    'patrol' => 'Leaders',
                ],
            ],
        ]),
    ]);

    $leader = Leader::factory()->create([
        'name' => 'Pat Leader',
        'bio' => 'Existing local profile.',
        'is_active' => true,
    ]);

    app(ImportOsmSectionLeadersJob::class)->handle(
        app(ImportOsmSectionLeadersService::class),
        $settings,
    );

    expect(Leader::query()->where('name', 'Pat Leader')->count())->toBe(1)
        ->and($leader->fresh()->bio)->toBe('Existing local profile.')
        ->and($leader->fresh()->is_active)->toBeTrue()
        ->and(LeaderSection::query()->whereBelongsTo($leader)->count())->toBe(0);
});

it('adds missing OSM photos to existing local leaders without overwriting their profile', function () {
    Queue::fake([
        ImportOsmLeaderPhoto::class,
    ]);

    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'existing-access-token';
    $settings->access_token_expires_at = now()->addYears(10)->toIso8601String();
    $settings->storeDirectorySnapshot(
        accountName: 'Pat Leader',
        accountEmail: 'pat@example.com',
        sections: [
            '301' => 'Example Group Beaver Colony',
        ],
        termsBySection: [
            '301' => [
                '402' => 'Summer 2026 (2026-04-13 to 2026-08-31)',
            ],
        ],
    );
    $settings->beavers_section_id = '301';
    $settings->save();

    $leader = Leader::factory()->create([
        'name' => 'Pat Leader',
        'bio' => 'Existing local profile.',
        'is_active' => true,
    ]);

    Saloon::fake([
        ListMembersRequest::class => MockResponse::make([
            'items' => [
                [
                    'firstname' => 'Pat',
                    'lastname' => 'Leader',
                    'photo_guid' => '11111111-2222-3333-4444-555555555555',
                    'patrol' => 'Leaders',
                    'scoutid' => 3100001,
                ],
            ],
        ]),
    ]);

    app(ImportOsmSectionLeadersJob::class)->handle(
        app(ImportOsmSectionLeadersService::class),
        $settings,
    );

    $leader->refresh();

    expect(Leader::query()->where('name', 'Pat Leader')->count())->toBe(1)
        ->and($leader->bio)->toBe('Existing local profile.')
        ->and($leader->is_active)->toBeTrue()
        ->and($leader->hasMedia('photo'))->toBeFalse();

    Queue::assertPushed(ImportOsmLeaderPhoto::class, function (ImportOsmLeaderPhoto $job) use ($leader): bool {
        return $job->leaderId === $leader->id
            && $job->scoutId === '3100001'
            && $job->photoGuid === '11111111-2222-3333-4444-555555555555';
    });
});

it('imports a larger OSM leader photo in a separate queued job', function () {
    Storage::fake('public');

    $leader = Leader::factory()->create([
        'name' => 'Alex Example',
    ]);

    Saloon::fake([
        GetMemberPhotoRequest::class => MockResponse::make(
            UploadedFile::fake()->image('osm-leader.jpg', 250, 250)->getContent(),
            200,
            ['Content-Type' => 'image/jpeg'],
        ),
    ]);

    app(ImportOsmLeaderPhoto::class, [
        'leaderId' => $leader->id,
        'scoutId' => '2785188',
        'photoGuid' => '9c0a5e22-9321-4631-b81e-f3e780dad2fa',
    ])->handle();

    $leader->refresh();

    expect($leader->hasMedia('photo'))->toBeTrue()
        ->and($leader->getFirstMedia('photo')->getCustomProperty('source'))->toBe('osm')
        ->and($leader->getFirstMedia('photo')->getCustomProperty('osm_scout_id'))->toBe('2785188')
        ->and($leader->getFirstMedia('photo')->getCustomProperty('osm_photo_guid'))->toBe('9c0a5e22-9321-4631-b81e-f3e780dad2fa');

    Saloon::assertSent(function (GetMemberPhotoRequest $request): bool {
        return str_contains(
            $request->resolveEndpoint(),
            '/member_photos/2785000/2785188/9c0a5e22-9321-4631-b81e-f3e780dad2fa/250x250_0.jpg',
        );
    });
});

it('continues importing leaders from other mapped sections when OSM refuses one section', function () {
    $settings = app(OsmSettings::class);
    $settings->refresh_token = 'refresh-token';
    $settings->access_token = 'existing-access-token';
    $settings->access_token_expires_at = now()->addYears(10)->toIso8601String();
    $settings->storeDirectorySnapshot(
        accountName: 'Pat Leader',
        accountEmail: 'pat@example.com',
        sections: [
            '301' => 'Example Group Beaver Colony',
            '701' => 'Example Group Scout Troop',
        ],
        termsBySection: [
            '301' => [
                '402' => 'Summer 2026 (2026-04-13 to 2026-08-31)',
            ],
            '701' => [
                '802' => 'Summer 2026 (2026-04-13 to 2026-08-31)',
            ],
        ],
    );
    $settings->beavers_section_id = '301';
    $settings->scouts_section_id = '701';
    $settings->save();

    Saloon::fake([
        MockResponse::make(['error' => 'method-not-allowed'], 405),
        MockResponse::make([
            'items' => [
                [
                    'firstname' => 'Sam',
                    'lastname' => 'Scout Leader',
                    'patrol' => 'Leaders',
                ],
            ],
        ]),
    ]);

    app(ImportOsmSectionLeadersJob::class)->handle(
        app(ImportOsmSectionLeadersService::class),
        $settings,
    );

    $leader = Leader::query()->with('sectionAssignments')->where('name', 'Sam Scout Leader')->sole();

    expect($leader->sectionAssignments)->toHaveCount(1)
        ->and($leader->sectionAssignments->sole()->section)->toBe(Section::SCOUTS);
});
