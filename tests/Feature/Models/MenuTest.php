<?php


use App\Models\Menu;

it('has a name', function () {
    // Arrange
    $menu = Menu::factory()->create();
    $namedMenu = Menu::factory()->create([
        'name' => 'scouts',
    ]);

    // Act & Assert
    expect($menu->name)->not()->toBeNull()
        ->and($menu->name)->toBeString()
        ->and($namedMenu->name)->toBe('scouts');

});

it('has a slug', function () {
// Arrange
    $menu = Menu::factory()->create();
    $namedMenu = Menu::factory()->create([
        'name' => 'Super Scouts',
    ]);

    // Act & Assert
    expect($menu->slug)->not()->toBeNull()
        ->and($menu->slug)->toBeString()
        ->and($namedMenu->slug)->toBe('super-scouts');

});

