<?php

use App\Filament\Blocks\ContactFormBlock;
use App\Filament\Blocks\GoogleMapBlock;
use App\Filament\Blocks\HeroBlock;
use App\Filament\Blocks\RichTextBlock;
use App\Models\Page;
use App\Settings\GroupProfileSettings;
use App\Settings\SectionSettings;
use Inertia\Testing\AssertableInertia as Assert;

it('renders published pages with serialized page builder blocks', function () {
    $page = Page::factory()->published()->create([
        'title' => 'About Squirrels',
        'slug' => 'about-squirrels',
    ]);

    $page->pageBuilderBlocks()->create([
        'block_type' => HeroBlock::class,
        'order' => 1,
        'data' => [
            'eyebrow' => 'Squirrels',
            'title' => 'Big adventures begin here',
            'body' => 'A warm welcome to our youngest section.',
            'image' => '/img/cubs-in-helmets-outdoors-jpg.jpg',
        ],
    ]);

    $this->get(route('page.show', ['page' => $page->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('Page/Show')
            ->where('page.title', 'About Squirrels')
            ->where('page.slug', 'about-squirrels')
            ->where('page.blocks.0.type', 'HeroBlock')
            ->where('page.blocks.0.data.title', 'Big adventures begin here'));
});

it('returns not found for draft pages', function () {
    $page = Page::factory()->create([
        'slug' => 'draft-page',
    ]);

    $this->get(route('page.show', ['page' => $page->slug]))
        ->assertNotFound();
});

it('replaces the group name shortcode in rich text blocks', function () {
    $groupProfileSettings = app(GroupProfileSettings::class);
    $groupProfileSettings->group_name = 'Example Scout Group';
    $groupProfileSettings->save();

    $page = Page::factory()->published()->create();
    $page->pageBuilderBlocks()->create([
        'block_type' => RichTextBlock::class,
        'order' => 1,
        'data' => [
            'content' => 'Welcome to **{{ group_name }}**.',
        ],
    ]);

    $this->get(route('page.show', ['page' => $page->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->where('page.blocks.0.data.content', "<p>Welcome to <strong>Example Scout Group</strong>.</p>\n"));
});

it('returns not found for disabled section pages', function (string $slug) {
    Page::factory()->published()->create([
        'title' => str($slug)->headline()->toString(),
        'slug' => $slug,
    ]);

    $sectionSettings = app(SectionSettings::class);
    $sectionSettings->enabled_section_slugs = [];
    $sectionSettings->save();

    $this->get(route('page.show', ['page' => $slug]))
        ->assertNotFound();
})->with([
    'explorers',
    'network',
]);

it('renders contact-specific page builder blocks', function () {
    $page = Page::factory()->published()->create([
        'title' => 'Contact',
        'slug' => 'contact',
    ]);

    $page->pageBuilderBlocks()->createMany([
        [
            'block_type' => ContactFormBlock::class,
            'order' => 1,
            'data' => [
                'eyebrow' => 'Contact form',
                'title' => 'Send us a message',
                'intro' => 'We would love to hear from you.',
                'submit_label' => 'Send message',
            ],
        ],
        [
            'block_type' => GoogleMapBlock::class,
            'order' => 2,
            'data' => [
                'eyebrow' => 'Google map',
                'title' => 'Find our HQ',
                'intro' => 'We meet at the local Scout headquarters.',
            ],
        ],
    ]);

    $this->get(route('page.show', ['page' => $page->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('Page/Show')
            ->where('page.blocks.0.type', 'ContactFormBlock')
            ->where('page.blocks.1.type', 'GoogleMapBlock'));
});
