<?php

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can load the create tag page', function () {
    Livewire::test(CreateTag::class)
        ->assertOk();
});

it('persists tags when creating them from filament', function () {
    Livewire::test(CreateTag::class)
        ->fillForm([
            'name' => 'Fundraising',
            'slug' => 'fundraising',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $tag = Tag::query()->where('slug', 'fundraising')->sole();

    expect($tag->name)->toBe('Fundraising');
});
