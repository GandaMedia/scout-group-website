<?php

use App\Filament\Pages\GroupProfileSettings as GroupProfileSettingsPage;
use App\Models\User;
use App\Settings\GroupProfileSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('allows admins to update the group profile', function () {
    Livewire::test(GroupProfileSettingsPage::class)
        ->fillForm([
            'group_name' => 'Example Scout Group',
            'group_short_name' => 'Example Scouts',
            'logo_short_label' => 'Example',
            'logo_stacked_line_1' => 'Example',
            'logo_stacked_line_2' => 'Scout Group',
            'website_url' => 'https://example-scouts.test',
            'mail_from_name' => 'Example Scout Group',
            'mail_from_address' => 'hello@example-scouts.test',
            'contact_recipient_name' => 'Example Leaders',
            'contact_recipient_email' => 'leaders@example-scouts.test',
            'headquarters_label' => 'Example Scout HQ',
            'headquarters_address' => "Example Scout HQ\nHigh Street",
            'map_embed_url' => 'https://maps.google.com/embed?pb=example',
            'charity_number' => '123456',
            'charity_register_url' => 'https://register-of-charities.example.test/123456',
            'district_name' => 'Example District Scouts',
            'district_url' => 'https://district.example.test',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings = app(GroupProfileSettings::class);

    expect($settings->group_name)->toBe('Example Scout Group')
        ->and($settings->group_short_name)->toBe('Example Scouts')
        ->and($settings->logo_short_label)->toBe('Example')
        ->and($settings->logo_stacked_line_1)->toBe('Example')
        ->and($settings->logo_stacked_line_2)->toBe('Scout Group')
        ->and($settings->mail_from_address)->toBe('hello@example-scouts.test')
        ->and($settings->contact_recipient_email)->toBe('leaders@example-scouts.test')
        ->and($settings->headquarters_label)->toBe('Example Scout HQ')
        ->and($settings->charity_number)->toBe('123456')
        ->and($settings->district_name)->toBe('Example District Scouts');
});
