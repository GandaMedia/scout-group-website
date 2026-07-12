<?php

use App\Models\Tag;
use Database\Seeders\TagSeeder;

test('tag seeder creates the section tags for news posts', function () {
    $this->seed(TagSeeder::class);

    $tags = Tag::query()
        ->orderBy('name')
        ->pluck('slug')
        ->all();

    expect($tags)->toBe([
        'beavers',
        'cubs',
        'explorers',
        'network',
        'scouts',
        'squirrels',
    ]);
});

test('tag seeder updates existing section tags instead of duplicating them', function () {
    Tag::query()->create([
        'name' => 'Squirrel Section',
        'slug' => 'squirrels',
    ]);

    $this->seed(TagSeeder::class);
    $this->seed(TagSeeder::class);

    expect(Tag::query()->where('slug', 'squirrels')->count())->toBe(1)
        ->and(Tag::query()->where('slug', 'squirrels')->value('name'))->toBe('Squirrels')
        ->and(Tag::query()->count())->toBe(6);
});
