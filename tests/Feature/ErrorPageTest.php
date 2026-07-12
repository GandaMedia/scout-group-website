<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders a branded Inertia error page in production', function () {
    $this->app->detectEnvironment(fn (): string => 'production');

    $this->get('/a-page-that-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
            ->has('groupProfile.name')
            ->has('groupProfile.logo.shortLabel'));
});

it('keeps the default exception response outside production', function () {
    $this->get('/a-page-that-does-not-exist')
        ->assertNotFound()
        ->assertHeaderMissing('X-Inertia');
});
