<?php

use App\Enums\WaitingListEntrySyncStatus;
use App\Models\WaitingListEntry;
use App\Settings\SectionSettings;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

it('renders a section specific waiting-list page', function () {
    config()->set('services.turnstile.site_key', 'site-key');
    config()->set('services.turnstile.secret_key', 'secret-key');

    $this->get(route('waiting-list.show', ['section' => 'beavers']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('WaitingList/Show')
            ->where('section.slug', 'beavers')
            ->where('section.label', 'Beavers')
            ->where('form.turnstile_site_key', 'site-key')
            ->where('form.is_configured', true)
        );
});

it('returns not found for disabled waiting-list section pages', function (string $section) {
    $sectionSettings = app(SectionSettings::class);
    $sectionSettings->enabled_section_slugs = [];
    $sectionSettings->save();

    $this->get(route('waiting-list.show', ['section' => $section]))
        ->assertNotFound();
})->with([
    'explorers',
    'network',
]);

it('stores a waiting-list submission and redirects with success feedback', function () {
    configureWaitingListTurnstile();

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $response = $this->from(route('waiting-list.show', ['section' => 'beavers']))
        ->post(route('waiting-list.store'), validWaitingListPayload());

    $response->assertRedirect(route('waiting-list.show', ['section' => 'beavers']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('waitingListSubmitted');

    $entry = WaitingListEntry::query()->sole();

    expect($entry->first_name)->toBe('Ada')
        ->and($entry->last_name)->toBe('Lovelace')
        ->and($entry->section_slug)->toBe('beavers')
        ->and($entry->parent_email)->toBe('parent@example.com')
        ->and($entry->postcode)->toBe('BN9 9AA')
        ->and($entry->sync_status)->toBe(WaitingListEntrySyncStatus::PENDING)
        ->and($entry->is_possible_duplicate)->toBeFalse()
        ->and($entry->submitted_at)->not->toBeNull()
        ->and($entry->sync_queued_at)->not->toBeNull();
});

it('stores blank notes as an empty string', function () {
    configureWaitingListTurnstile();

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $response = $this->from(route('waiting-list.show', ['section' => 'beavers']))
        ->post(route('waiting-list.store'), validWaitingListPayload([
            'notes' => null,
        ]));

    $response->assertRedirect(route('waiting-list.show', ['section' => 'beavers']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('waitingListSubmitted');

    expect(WaitingListEntry::query()->sole()->notes)->toBe('');
});

it('rejects invalid waiting-list submissions', function () {
    configureWaitingListTurnstile();

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $response = $this->from(route('waiting-list.show', ['section' => 'beavers']))
        ->post(route('waiting-list.store'), [
            'first_name' => '',
            'last_name' => '',
            'date_of_birth' => now()->addDay()->toDateString(),
            'section_slug' => 'invalid-section',
            'parent_name' => '',
            'parent_email' => 'not-an-email',
            'parent_phone' => '',
            'postcode' => '',
            'turnstile_token' => '',
        ]);

    $response->assertRedirect(route('waiting-list.show', ['section' => 'beavers']))
        ->assertSessionHasErrors([
            'first_name',
            'last_name',
            'date_of_birth',
            'section_slug',
            'parent_name',
            'parent_email',
            'parent_phone',
            'postcode',
            'turnstile_token',
        ]);

    expect(WaitingListEntry::query()->count())->toBe(0);
});

it('rejects waiting-list submissions for disabled sections', function () {
    configureWaitingListTurnstile();

    $sectionSettings = app(SectionSettings::class);
    $sectionSettings->enabled_section_slugs = [];
    $sectionSettings->save();

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $this->from(route('home'))
        ->post(route('waiting-list.store'), validWaitingListPayload([
            'section_slug' => 'network',
        ]))
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors(['section_slug']);

    expect(WaitingListEntry::query()->count())->toBe(0);
});

it('stores duplicate submissions and holds them for manual review', function () {
    configureWaitingListTurnstile();

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $this->from(route('waiting-list.show', ['section' => 'beavers']))
        ->post(route('waiting-list.store'), validWaitingListPayload())
        ->assertRedirect(route('waiting-list.show', ['section' => 'beavers']));

    $this->from(route('waiting-list.show', ['section' => 'beavers']))
        ->post(route('waiting-list.store'), validWaitingListPayload([
            'parent_email' => 'other-parent@example.com',
            'notes' => 'Second request from another parent.',
        ]))
        ->assertRedirect(route('waiting-list.show', ['section' => 'beavers']));

    expect(WaitingListEntry::query()->count())->toBe(2);

    $duplicate = WaitingListEntry::query()->latest('id')->firstOrFail();

    expect($duplicate->sync_status)->toBe(WaitingListEntrySyncStatus::HELD_DUPLICATE)
        ->and($duplicate->is_possible_duplicate)->toBeTrue()
        ->and($duplicate->duplicate_reason)->not->toBeNull()
        ->and($duplicate->duplicate_detected_at)->not->toBeNull()
        ->and($duplicate->sync_queued_at)->toBeNull();
});

function configureWaitingListTurnstile(): void
{
    config()->set('services.turnstile.site_key', 'site-key');
    config()->set('services.turnstile.secret_key', 'secret-key');
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validWaitingListPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'date_of_birth' => '2018-03-14',
        'section_slug' => 'beavers',
        'parent_name' => 'Parent Lovelace',
        'parent_email' => 'parent@example.com',
        'parent_phone' => '01234 567890',
        'postcode' => 'bn9 9aa',
        'notes' => 'Ada would like to join next term.',
        'turnstile_token' => 'turnstile-token',
    ], $overrides);
}
