<?php

use App\Enums\PageStatus;
use App\Filament\Blocks\ContactDetailsBlock;
use App\Filament\Blocks\ContactFormBlock;
use App\Filament\Blocks\GoogleMapBlock;
use App\Filament\Blocks\HeroBlock;
use App\Filament\Blocks\RichTextBlock;
use App\Filament\Blocks\SectionLeadersBlock;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Database\Seeders\MenuItemSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('page seeder creates the client section pages with published content', function () {
    $this->seed(PageSeeder::class);

    expect(Page::query()->count())->toBe(10);

    $squirrelsPage = Page::query()->where('title', 'Squirrels')->first();
    $beaversPage = Page::query()->where('title', 'Beavers')->first();
    $cubsPage = Page::query()->where('title', 'Cubs')->first();
    $scoutsPage = Page::query()->where('title', 'Scouts')->first();
    $explorersPage = Page::query()->where('title', 'Explorers')->first();
    $networkPage = Page::query()->where('title', 'Network')->first();
    $contactPage = Page::query()->where('title', 'Contact')->first();
    $privacyPage = Page::query()->where('slug', 'privacy')->first();
    $cookiePolicyPage = Page::query()->where('slug', 'cookie-policy')->first();
    $termsPage = Page::query()->where('slug', 'terms')->first();

    expect($squirrelsPage)->not->toBeNull()
        ->and($squirrelsPage->status)->toBe(PageStatus::PUBLISHED)
        ->and($squirrelsPage->slug)->toBe('squirrels')
        ->and($squirrelsPage->pageBuilderBlocks)->toHaveCount(5)
        ->and($squirrelsPage->pageBuilderBlocks->first()?->block_type)->toBe(HeroBlock::class)
        ->and($squirrelsPage->pageBuilderBlocks->get(2)?->block_type)->toBe(RichTextBlock::class)
        ->and($squirrelsPage->pageBuilderBlocks->get(3)?->block_type)->toBe(SectionLeadersBlock::class)
        ->and($beaversPage)->not->toBeNull()
        ->and($beaversPage->pageBuilderBlocks)->toHaveCount(5)
        ->and($cubsPage)->not->toBeNull()
        ->and($cubsPage->pageBuilderBlocks)->toHaveCount(5)
        ->and($scoutsPage)->not->toBeNull()
        ->and($scoutsPage->pageBuilderBlocks)->toHaveCount(5)
        ->and($explorersPage)->not->toBeNull()
        ->and($explorersPage->slug)->toBe('explorers')
        ->and($explorersPage->pageBuilderBlocks)->toHaveCount(5)
        ->and(data_get($explorersPage->pageBuilderBlocks->get(2), 'data.content'))
        ->toContain('https://www.scouts.org.uk/explorers/')
        ->not->toContain('To be confirmed')
        ->and($networkPage)->not->toBeNull()
        ->and($networkPage->slug)->toBe('network')
        ->and($networkPage->pageBuilderBlocks)->toHaveCount(5)
        ->and(data_get($networkPage->pageBuilderBlocks->get(2), 'data.content'))
        ->toContain('https://www.scouts.org.uk/network')
        ->not->toContain('To be confirmed')
        ->and($contactPage)->not->toBeNull()
        ->and($contactPage->slug)->toBe('contact')
        ->and($contactPage->pageBuilderBlocks)->toHaveCount(6)
        ->and($contactPage->pageBuilderBlocks->get(1)?->block_type)->toBe(ContactDetailsBlock::class)
        ->and($contactPage->pageBuilderBlocks->get(2)?->block_type)->toBe(ContactFormBlock::class)
        ->and($contactPage->pageBuilderBlocks->get(3)?->block_type)->toBe(GoogleMapBlock::class)
        ->and($contactPage->pageBuilderBlocks->get(4)?->block_type)->toBe(RichTextBlock::class)
        ->and($privacyPage?->status)->toBe(PageStatus::PUBLISHED)
        ->and($privacyPage?->pageBuilderBlocks)->toHaveCount(2)
        ->and(data_get($privacyPage?->pageBuilderBlocks->last(), 'data.content'))
        ->toContain('{{ group_name }}')
        ->toContain('https://www.scouts.org.uk/about-us/policy/data-protection-policy/')
        ->and($cookiePolicyPage?->status)->toBe(PageStatus::PUBLISHED)
        ->and($cookiePolicyPage?->pageBuilderBlocks)->toHaveCount(2)
        ->and(data_get($cookiePolicyPage?->pageBuilderBlocks->last(), 'data.content'))
        ->toContain('{{ group_name }}')
        ->toContain('https://www.scouts.org.uk/about-us/policy/cookie-policy/')
        ->and($termsPage?->status)->toBe(PageStatus::PUBLISHED)
        ->and($termsPage?->pageBuilderBlocks)->toHaveCount(2)
        ->and(data_get($termsPage?->pageBuilderBlocks->last(), 'data.content'))
        ->toContain('{{ group_name }}')
        ->toContain('https://www.scouts.org.uk/about-us/policy/terms-conditions/');
});

test('page seeder updates existing section pages instead of duplicating them', function () {
    Page::factory()->create([
        'title' => 'Squirrels',
        'slug' => 'squirrels',
        'status' => PageStatus::DRAFT,
    ]);

    $this->seed(PageSeeder::class);
    $this->seed(PageSeeder::class);

    $squirrelsPage = Page::query()->where('title', 'Squirrels')->first();

    expect(Page::query()->where('title', 'Squirrels')->count())->toBe(1)
        ->and(Page::query()->count())->toBe(10)
        ->and($squirrelsPage)->not->toBeNull()
        ->and($squirrelsPage->status)->toBe(PageStatus::PUBLISHED)
        ->and($squirrelsPage->pageBuilderBlocks)->toHaveCount(5);
});

test('menu item seeder adds a contact link alongside the sections submenu without duplicates', function () {
    $this->seed(PageSeeder::class);
    $this->seed(MenuSeeder::class);

    $this->seed(MenuItemSeeder::class);
    $this->seed(MenuItemSeeder::class);

    $menu = Menu::query()->where('name', 'Main Menu')->firstOrFail();
    $footerMenu = Menu::query()->where('name', 'Footer Menu')->firstOrFail();
    $contactPage = Page::query()->where('slug', 'contact')->firstOrFail();
    $sectionsItem = MenuItem::query()
        ->where('menu_id', $menu->id)
        ->where('name', 'Sections')
        ->firstOrFail();

    expect(MenuItem::query()
        ->where('menu_id', $menu->id)
        ->where('name', 'Contact')
        ->where('menuable_type', Page::class)
        ->where('menuable_id', $contactPage->id)
        ->count())->toBe(1)
        ->and(MenuItem::query()
            ->where('menu_id', $menu->id)
            ->where('parent_id', $sectionsItem->id)
            ->count())->toBe(6)
        ->and(MenuItem::query()->where('menu_id', $footerMenu->id)->count())->toBe(3);

    $orderedTopLevelItems = MenuItem::query()
        ->where('menu_id', $menu->id)
        ->whereNull('parent_id')
        ->orderBy('order_column')
        ->pluck('name')
        ->values()
        ->all();

    expect($orderedTopLevelItems)->toBe([
        'Sections',
        'Contact',
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->where('menu.0.name', 'Sections')
            ->where('menu.1.name', 'News')
            ->where('menu.2.name', 'Calendar')
            ->where('menu.3.name', 'Contact')
            ->missing('menu.4'));
});
