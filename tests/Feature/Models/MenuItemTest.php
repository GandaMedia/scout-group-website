<?php

use App\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;

it('has a name', function () {
    // Arrange
    $menuItem = MenuItem::factory()->create();
    $namedMenuItem = MenuItem::factory()->create([
        'name' => 'scouts',
    ]);

    // Act & Assert
    expect($menuItem->name)->not()->toBeNull()
        ->and($menuItem->name)->toBeString()
        ->and($namedMenuItem->name)->toBe('scouts');

});

it('belongs to a menu', closure: function () {
    $menuItem = MenuItem::factory()->create();
    $menu = Menu::factory()->create();

    $menuedItem = MenuItem::factory()->create([
        'menu_id' => $menu->id,
    ]);

    // Act & Assert
    expect($menuItem->menu)->not()->toBeNull()
        ->and($menuItem->menu)->toBeInstanceOf(Menu::class)
        ->and($menuedItem->menu)->toBeInstanceOf(Menu::class)
        ->and($menuedItem->menu_id)->toBe($menu->id);
});

it('has a type', function () {
    // Arrange
    $menuItem = MenuItem::factory()->create();
    $linkMenuItem = MenuItem::factory()->create([
        'type' => MenuItemType::LINK
    ]);

    $modelMenuItem = MenuItem::factory()->create([
        'type' => MenuItemType::MODEL
    ]);

    $linkMenuItem->refresh();
    $modelMenuItem->refresh();

    // Act & Assert
    expect($menuItem->type)->not()->toBeNull()
        ->and($menuItem->type)->toBeInstanceOf(MenuItemType::class)
        ->and($linkMenuItem->type)->toBe(MenuItemType::LINK)
        ->and($modelMenuItem->type)->toBe(MenuItemType::MODEL);

});

it('can be a page', function () {
    // Arrange
    $page1 = Page::factory()->create();
    $page2 = Page::factory()->create();
    $page3 = Page::factory()->create();

    $menuItem = MenuItem::factory()->for(
        $page2, 'menuable'
    )->create([
        'type' => MenuItemType::MODEL,
    ]);


    // Assert
    expect($page1->menuItems->contains($menuItem))->not->toBeTrue()
        ->and($page2->menuItems->contains($menuItem))->toBeTrue()
        ->and($page3->menuItems->contains($menuItem))->not->toBeTrue()
        ->and($menuItem->menuable)->toBeInstanceOf(Page::class)
        ->and($menuItem->menuable->id)->toBe($page2->id);
});
it('can be a link', function () {
    // Arrange
    $menuItem = MenuItem::factory()->withLink()->create([
        'type' => MenuItemType::LINK,
    ]);
    $menuItem2 = MenuItem::factory()->create([
        'type' => MenuItemType::LINK,
        'link' => 'https://www.google.com'
    ]);


    // Assert
    expect($menuItem->menuable)->toBeNull()
        ->and($menuItem->menuable)->not()->toBeInstanceOf(Page::class)
        ->and($menuItem->link)->not()->toBeNull()
        ->and($menuItem->link)->toBeUrl()
        ->and($menuItem2->link)->toBe('https://www.google.com');

});

it('can be nested', function () {
    // Arrange
    $parent_menuItem1 = MenuItem::factory()->create();
    $parent_menuItem2 = MenuItem::factory()->create();
    $child_menuItem1 = MenuItem::factory()->create(['parent_id' => $parent_menuItem1->id]);
    $child_menuItem2 = MenuItem::factory()->create(['parent_id' => $parent_menuItem2->id]);
    $child_menuItem3 = MenuItem::factory()->create(['parent_id' => $parent_menuItem2->id]);


    // Act

    // Assert
    expect($child_menuItem1->parent->id)->toBe($parent_menuItem1->id)
        ->and($child_menuItem2->parent->id)->toBe($parent_menuItem2->id)
        ->and($child_menuItem3->parent->id)->toBe($parent_menuItem2->id)
        ->and($parent_menuItem1->children->contains($child_menuItem1))->toBeTrue()
        ->and($parent_menuItem1->children->contains($child_menuItem2))->not->toBeTrue()
        ->and($parent_menuItem2->children->contains($child_menuItem2))->toBeTrue()
        ->and($parent_menuItem2->children->contains($child_menuItem3))->toBeTrue();

});
