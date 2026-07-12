<?php

namespace Database\Seeders;

use App\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu = Menu::query()->where('name', 'Main Menu')->firstOrFail();
        $contactPage = Page::query()->firstWhere('slug', 'contact');
        $sectionSlugs = ['squirrels', 'beavers', 'cubs', 'scouts', 'explorers', 'network'];

        $pages = Page::query()
            ->whereIn('slug', $sectionSlugs)
            ->get()
            ->keyBy('slug');

        $sectionMenuItem = MenuItem::firstOrCreate([
            'menu_id' => $menu->id,
            'name' => 'Sections',
            'type' => MenuItemType::SUBMENU,
        ]);

        foreach ($sectionSlugs as $slug) {
            $page = $pages->get($slug);

            if (! $page instanceof Page) {
                continue;
            }

            MenuItem::firstOrCreate([
                'menu_id' => $menu->id,
                'parent_id' => $sectionMenuItem->id,
                'name' => $page->title,
                'type' => MenuItemType::MODEL,
                'menuable_type' => Page::class,
                'menuable_id' => $page->id,
            ]);
        }

        if ($contactPage instanceof Page) {
            $contactItem = MenuItem::firstOrCreate([
                'menu_id' => $menu->id,
                'name' => 'Contact',
                'type' => MenuItemType::MODEL,
                'menuable_type' => Page::class,
                'menuable_id' => $contactPage->id,
            ]);

            $highestTopLevelOrder = MenuItem::query()
                ->where('menu_id', $menu->id)
                ->whereNull('parent_id')
                ->whereKeyNot($contactItem->getKey())
                ->max('order_column');

            $contactItem->order_column = (int) $highestTopLevelOrder + 1;
            $contactItem->save();
        }

        $this->seedFooterMenu();
    }

    private function seedFooterMenu(): void
    {
        $footerMenu = Menu::query()->where('name', 'Footer Menu')->firstOrFail();
        $legalPages = Page::query()
            ->whereIn('slug', ['privacy', 'cookie-policy', 'terms'])
            ->get()
            ->keyBy('slug');

        foreach ([
            'privacy' => 'Data protection',
            'cookie-policy' => 'Cookie policy',
            'terms' => 'Terms and conditions',
        ] as $slug => $name) {
            $page = $legalPages->get($slug);

            if (! $page instanceof Page) {
                continue;
            }

            MenuItem::query()->firstOrCreate([
                'menu_id' => $footerMenu->id,
                'name' => $name,
                'type' => MenuItemType::MODEL,
                'menuable_type' => Page::class,
                'menuable_id' => $page->id,
            ]);
        }
    }
}
