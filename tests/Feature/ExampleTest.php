<?php

use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menu', fn (Collection $menu): bool => $menu->contains(
                fn (array $item): bool => $item['name'] === 'Calendar'
                    && $item['link'] === route('calendar')
                    && $item['external'] === false
            ))
        );
});
