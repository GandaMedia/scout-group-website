<?php

use App\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use App\Settings\SectionSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

it('shares news and tools in the main menu after sections', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $leader = User::factory()->create();
    $leader->givePermissionTo('access leader tools');
    $this->actingAs($leader);
    Cache::forget('mainMenu');

    $menu = Menu::factory()->create(['name' => 'Main Menu']);

    $sections = MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'name' => 'Sections',
        'type' => MenuItemType::SUBMENU,
    ]);

    MenuItem::factory()->withLink()->create([
        'menu_id' => $menu->id,
        'parent_id' => $sections->id,
        'name' => 'Squirrels',
        'type' => MenuItemType::LINK,
    ]);

    MenuItem::factory()->withLink()->create([
        'menu_id' => $menu->id,
        'name' => 'Contact',
        'type' => MenuItemType::LINK,
    ]);

    $this->get(route('news.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menu', function (Collection $sharedMenu): bool {
                $menu = $sharedMenu->all();

                expect(array_column($menu, 'name'))->toBe([
                    'Sections',
                    'News',
                    'Tools',
                    'Calendar',
                    'Contact',
                ]);

                $tools = $menu[2];

                expect($tools['link'])->toBeNull()
                    ->and(array_column($tools['children'], 'name'))->toBe([
                        'Projects',
                        'Meal planner',
                    ])
                    ->and($tools['children'][0]['link'])->toBe(route('tools.projects'))
                    ->and($tools['children'][1]['link'])->toBe(route('meal-planner'));

                return true;
            }));
});

it('hides tools from guests and users without leader tools permission', function () {
    Cache::forget('mainMenu');

    $this->get(route('news.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menu', fn (Collection $menu): bool => ! $menu->contains('id', 'tools')));

    $this->actingAs(User::factory()->pendingApproval()->create())
        ->get(route('news.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menu', fn (Collection $menu): bool => ! $menu->contains('id', 'tools')));
});

it('omits disabled section page children from the shared main menu', function () {
    Cache::forget('mainMenu');

    $sectionSettings = app(SectionSettings::class);
    $sectionSettings->enabled_section_slugs = ['beavers'];
    $sectionSettings->save();

    $menu = Menu::factory()->create(['name' => 'Main Menu']);

    $sections = MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'name' => 'Sections',
        'type' => MenuItemType::SUBMENU,
    ]);

    $squirrelsPage = Page::factory()->published()->create([
        'title' => 'Squirrels',
        'slug' => 'squirrels',
    ]);
    $beaversPage = Page::factory()->published()->create([
        'title' => 'Beavers',
        'slug' => 'beavers',
    ]);

    MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'parent_id' => $sections->id,
        'name' => 'Squirrels',
        'type' => MenuItemType::MODEL,
        'menuable_type' => Page::class,
        'menuable_id' => $squirrelsPage->id,
    ]);

    MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'parent_id' => $sections->id,
        'name' => 'Beavers',
        'type' => MenuItemType::MODEL,
        'menuable_type' => Page::class,
        'menuable_id' => $beaversPage->id,
    ]);

    $this->get(route('news.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menu.0.name', 'Sections')
            ->where('menu.0.children.0.name', 'Beavers')
            ->missing('menu.0.children.1'));
});
