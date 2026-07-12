<?php

use App\Enums\Section;
use App\Filament\Pages\SectionSettings as SectionSettingsPage;
use App\Models\Page;
use App\Models\User;
use App\Settings\SectionSettings;
use Database\Seeders\PageSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('allows admins to update section availability and titles', function () {
    $this->seed(PageSeeder::class);

    Livewire::test(SectionSettingsPage::class)
        ->assertFormFieldExists('sections.explorers.enabled')
        ->assertFormFieldExists('sections.network.title')
        ->fillForm([
            'sections' => [
                'squirrels' => ['enabled' => true, 'title' => 'Squirrels'],
                'beavers' => ['enabled' => true, 'title' => 'Beavers'],
                'cubs' => ['enabled' => true, 'title' => 'Cubs'],
                'scouts' => ['enabled' => true, 'title' => 'Scouts'],
                'explorers' => ['enabled' => false, 'title' => 'Explorer Unit'],
                'network' => ['enabled' => true, 'title' => 'Scout Network'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings = app(SectionSettings::class);

    expect($settings->enabledSectionSlugs())->toBe([
        'scouts',
        'cubs',
        'beavers',
        'squirrels',
        'network',
    ])
        ->and(Page::query()->where('slug', 'explorers')->value('title'))->toBe('Explorer Unit')
        ->and(Page::query()->where('slug', 'network')->value('title'))->toBe('Scout Network');
});

it('mounts a page-builder content editor for section pages', function () {
    $this->seed(PageSeeder::class);

    Livewire::test(SectionSettingsPage::class)
        ->assertActionExists(TestAction::make('edit_explorers_content')->schemaComponent('explorers_content_actions'))
        ->mountAction(TestAction::make('edit_explorers_content')->schemaComponent('explorers_content_actions'))
        ->assertFormFieldExists('title')
        ->assertFormFieldExists('content')
        ->fillForm([
            'title' => 'Explorer Scouts',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(Page::query()->where('slug', Section::EXPLORERS->slug())->value('title'))
        ->toBe('Explorer Scouts');
});
