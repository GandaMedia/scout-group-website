<?php

use App\Settings\GroupProfileSettings;
use Inertia\Testing\AssertableInertia as Assert;

it('renders complete social metadata when SSR is unavailable', function () {
    $settings = app(GroupProfileSettings::class);
    $settings->group_name = 'Example Scout Group';
    $settings->save();

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('<title>Example Scout Group</title>', false)
        ->assertSee('name="description"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('property="og:site_name"', false)
        ->assertSee('property="og:description"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        ->assertSee('name="twitter:description"', false)
        ->assertSee('name="twitter:image"', false);
});

it('uses the current request origin instead of a stale configured website URL', function () {
    $settings = app(GroupProfileSettings::class);
    $settings->website_url = 'https://retired-host.example';
    $settings->save();

    $this->get('https://scouts.example/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('requestOrigin', 'https://scouts.example'));
});
