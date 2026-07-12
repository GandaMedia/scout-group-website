<?php

use App\Filament\Pages\HomePageSettings as HomePageSettingsPage;
use App\Models\User;
use App\Settings\HomePageSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('allows admins to update home page section cards', function () {
    Livewire::test(HomePageSettingsPage::class)
        ->fillForm([
            'section_cards' => [
                [
                    'section' => 'Squirrels',
                    'age_range' => '4 - 6 years old',
                    'time_slot' => 'Tuesdays: 5 - 6pm',
                    'description' => 'Small adventures for our youngest members.',
                    'page_slug' => 'squirrels',
                ],
                [
                    'section' => 'Scouts',
                    'age_range' => '10½ - 14 years old',
                    'time_slot' => 'Wednesdays: 7:15 - 8:45pm',
                    'description' => 'Outdoor adventures and skills for life.',
                    'page_slug' => 'scouts',
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings = app(HomePageSettings::class);

    expect($settings->section_cards)->toHaveCount(2)
        ->and($settings->section_cards[0]['description'])->toBe('Small adventures for our youngest members.')
        ->and($settings->section_cards[1]['time_slot'])->toBe('Wednesdays: 7:15 - 8:45pm');
});
