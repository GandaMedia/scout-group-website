<?php

use App\Enums\PageStatus;
use App\Enums\Section;
use App\Filament\Blocks\SectionLeadersBlock;
use App\Models\Leader;
use App\Models\LeaderSection;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function attachLeaderBlockTestPhoto(Leader $leader, string $filename): Leader
{
    $leader
        ->addMedia(UploadedFile::fake()->image($filename, 900, 900))
        ->toMediaCollection('photo');

    return $leader->fresh('media');
}

it('serializes active section leaders in assignment order', function () {
    Storage::fake('public');

    $secondLeader = attachLeaderBlockTestPhoto(Leader::factory()->create([
        'name' => 'Second Leader',
        'scout_name' => 'Badger',
        'bio' => 'Second **leader** bio.',
        'fun_fact' => 'Can juggle.',
    ]), 'second.jpg');

    $firstLeader = attachLeaderBlockTestPhoto(Leader::factory()->create([
        'name' => 'First Leader',
        'scout_name' => 'Penguin',
        'bio' => 'First leader bio.',
        'fun_fact' => 'Loves kayaking.',
    ]), 'first.jpg');

    $secondAssignment = LeaderSection::factory()->for($secondLeader)->section(Section::SCOUTS)->create();
    $firstAssignment = LeaderSection::factory()->for($firstLeader)->section(Section::SCOUTS)->create();
    LeaderSection::factory()->for(Leader::factory()->create())->section(Section::CUBS)->create([
        'order_column' => 1,
    ]);
    LeaderSection::factory()->for(Leader::factory()->inactive()->create())->section(Section::SCOUTS)->create([
        'order_column' => 3,
    ]);

    $secondAssignment->update(['order_column' => 2]);
    $firstAssignment->update(['order_column' => 1]);

    $data = SectionLeadersBlock::formatForSingleView([
        'section' => Section::SCOUTS->value,
        'eyebrow' => 'Meet the troop',
        'title' => 'Scout leaders',
        'intro' => 'The team behind the adventures.',
    ]);

    expect($data['leaders'])->toHaveCount(2)
        ->and($data['leaders'][0]['name'])->toBe('First Leader')
        ->and($data['leaders'][0]['scout_name'])->toBe('Penguin')
        ->and($data['leaders'][0]['bio'])->toContain('First leader bio.')
        ->and($data['leaders'][0]['photo'])->toBe($firstLeader->photoUrl('card'))
        ->and($data['leaders'][1]['name'])->toBe('Second Leader');
});

it('returns an empty leader list when there are no active leaders for the selected section', function () {
    LeaderSection::factory()
        ->for(Leader::factory()->inactive())
        ->section(Section::BEAVERS)
        ->create();

    $data = SectionLeadersBlock::formatForSingleView([
        'section' => Section::BEAVERS->value,
    ]);

    expect($data['leaders'])->toBe([]);
});

it('passes section leader block data to the public page', function () {
    Storage::fake('public');

    $leader = attachLeaderBlockTestPhoto(Leader::factory()->create([
        'name' => 'Alex Example',
        'scout_name' => 'Penguin',
        'bio' => 'Loves a campfire.',
        'fun_fact' => 'Once won a water fight.',
    ]), 'alex.jpg');

    LeaderSection::factory()->for($leader)->section(Section::SCOUTS)->create([
        'order_column' => 1,
    ]);

    $page = Page::factory()->create([
        'title' => 'Scouts',
        'slug' => 'scouts',
        'status' => PageStatus::PUBLISHED,
    ]);

    $page->pageBuilderBlocks()->create([
        'block_type' => SectionLeadersBlock::class,
        'order' => 1,
        'data' => [
            'section' => Section::SCOUTS->value,
            'eyebrow' => 'Meet the team',
            'title' => 'Meet our Scout leaders',
            'intro' => 'Friendly volunteers running weekly adventures.',
        ],
    ]);

    $this->get(route('page.show', ['page' => $page->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('Page/Show')
            ->where('page.blocks.0.type', 'SectionLeadersBlock')
            ->where('page.blocks.0.data.title', 'Meet our Scout leaders')
            ->where('page.blocks.0.data.leaders.0.name', 'Alex Example')
            ->where('page.blocks.0.data.leaders.0.scout_name', 'Penguin')
            ->where('page.blocks.0.data.leaders.0.fun_fact', 'Once won a water fight.'));
});
