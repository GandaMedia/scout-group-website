<?php

use App\Filament\Pages\ContactSettings as ContactSettingsPage;
use App\Models\User;
use App\Settings\ContactSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('allows admins to update contact settings', function () {
    Livewire::test(ContactSettingsPage::class)
        ->fillForm([
            'success_message' => 'Thanks, we will get back to you.',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings = app(ContactSettings::class);

    expect($settings->success_message)->toBe('Thanks, we will get back to you.');
});
