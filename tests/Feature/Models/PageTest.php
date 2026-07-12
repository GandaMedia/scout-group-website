<?php

use App\Enums\PageStatus;
use App\Filament\Blocks\HeroBlock;
use App\Models\Page;
use Illuminate\Support\Facades\Storage;

it('has a name', function () {

    // Arrange
    $page = Page::factory()->create();
    $namedPage = Page::factory()->create(['title' => 'Test Page']);

    // Act & Assert
    expect($page->title)->not->toBeNull()
        ->and($page->title)->toBeString()
        ->and($namedPage->title)->not->toBeNull()
        ->and($namedPage->title)->toBeString()
        ->and($namedPage->title)->toBe('Test Page');

});

it('can own page builder blocks', function () {
    $page = Page::factory()->create();

    $page->pageBuilderBlocks()->create([
        'block_type' => HeroBlock::class,
        'order' => 1,
        'data' => [
            'title' => 'Ready for adventure',
        ],
    ]);

    expect($page->pageBuilderBlocks)->toHaveCount(1)
        ->and($page->pageBuilderBlocks->first()?->block_type)->toBe(HeroBlock::class)
        ->and($page->pageBuilderBlocks->first()?->data['title'])->toBe('Ready for adventure');
});

it('has a slug', function () {
    // Arrange
    $page = Page::factory()->create();
    $namedPage = Page::factory()->create(['title' => 'Test Page']);
    $sluggedPage = Page::factory()->create(['slug' => 'test-slug']);

    // Act & Assert
    expect($page->slug)->not->toBeNull()
        ->and($page->slug)->toBeString()
        ->and($namedPage->slug)->not->toBeNull()
        ->and($namedPage->slug)->toBeString()
        ->and($namedPage->slug)->toBe('test-page')
        ->and($sluggedPage->slug)->not->toBeNull()
        ->and($sluggedPage->slug)->toBeString()
        ->and($sluggedPage->slug)->toBe('test-slug');
});

it('can have pictures', function () {
    Storage::fake('public');

    $page = Page::factory()->create();
    $pictureCount = rand(1, 5);
    $pageWithPictures = Page::factory()->withPictures($pictureCount)->create();

    expect($page->getMedia('pictures'))->toHaveCount(0)
        ->and($pageWithPictures->getMedia('pictures'))->toHaveCount($pictureCount);
});

it('can be published', function () {
    $draftPage = Page::factory()->create();
    $publishedPage = Page::factory()->published()->create();

    expect($draftPage->status)->toBe(PageStatus::DRAFT)
        ->and($publishedPage->status)->toBe(PageStatus::PUBLISHED)
        ->and(Page::published()->pluck('id'))->toContain($publishedPage->id)
        ->not->toContain($draftPage->id);
});
