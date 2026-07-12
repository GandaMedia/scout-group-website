<?php

namespace App\Http\Middleware;

use App\Enums\MenuItemType;
use App\Enums\Section;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Settings\GroupProfileSettings;
use App\Settings\SectionSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        if (is_file(public_path('hot'))) {
            return $request->header('X-Inertia-Version');
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $menu = Cache::flexible('mainMenu', [600, 3600], function (): array {
            $menu = Menu::with('menuItems')->firstWhere('name', 'Main Menu');

            return $this->getMenu($menu);
        });

        if ($request->user()?->can('access leader tools') !== true) {
            $menu = collect($menu)
                ->reject(static fn (array $item): bool => $item['id'] === 'tools')
                ->values()
                ->all();
        }

        $groupProfileSettings = app(GroupProfileSettings::class);

        return [
            ...parent::share($request),
            'name' => $groupProfileSettings->group_name,
            'requestOrigin' => $request->getSchemeAndHttpHost(),
            'groupProfile' => [
                'name' => $groupProfileSettings->group_name,
                'shortName' => $groupProfileSettings->group_short_name,
                'logo' => [
                    'shortLabel' => $groupProfileSettings->logo_short_label,
                    'stackedLine1' => $groupProfileSettings->logo_stacked_line_1,
                    'stackedLine2' => $groupProfileSettings->logo_stacked_line_2,
                ],
                'websiteUrl' => $groupProfileSettings->website_url,
                'headquartersLabel' => $groupProfileSettings->headquarters_label,
                'headquartersAddress' => $groupProfileSettings->headquarters_address,
                'charityNumber' => $groupProfileSettings->charity_number,
                'charityRegisterUrl' => $groupProfileSettings->charity_register_url,
                'districtName' => $groupProfileSettings->district_name,
                'districtUrl' => $groupProfileSettings->district_url,
            ],
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'flash' => [
                'contactEnquirySubmitted' => $request->session()->get('contactEnquirySubmitted'),
                'waitingListSubmitted' => $request->session()->get('waitingListSubmitted'),
            ],
            'auth' => [
                'user' => $request->user(),
                'approvalStatus' => $request->user()?->approval_status?->value,
                'canAccessLeaderTools' => $request->user()?->can('access leader tools') ?? false,
                'canAccessAdmin' => $request->user()?->can('access admin') ?? false,
                'adminUrl' => $request->user()?->can('access admin') === true
                    ? route('filament.admin.pages.dashboard')
                    : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'menu' => $menu,
        ];
    }

    private function getMenu(?Menu $menu): array
    {
        if (! $menu instanceof Menu) {
            return [$this->calendarMenuItem()];
        }

        $menuItems = $menu->menuItems()
            ->orderBy('order_column')
            ->whereNull('parent_id')
            ->get()
            ->map(fn (MenuItem $item): ?array => $this->getMenuItem($item))
            ->filter()
            ->values();

        $menuItems = $this->insertUtilityItemsAfterSections($menuItems);

        if ($menuItems->contains(fn (array $item): bool => $item['link'] === route('calendar'))) {
            return $this->moveContactToEnd($menuItems)->toArray();
        }

        return $this->moveContactToEnd(
            $menuItems->push($this->calendarMenuItem()),
        )->toArray();
    }

    public function getMenuItem(MenuItem $item): ?array
    {
        if ($this->isDisabledSectionPageMenuItem($item)) {
            return null;
        }

        if ($item->type === MenuItemType::LINK) {
            $link = $item->link;
            $external = true;
        }

        if ($item->type === MenuItemType::MODEL) {
            $link = $item->menuable->getShowUrl();
            $external = false;
        }

        $children = $item->children
            ->sortBy('order_column')
            ->map(fn (MenuItem $child): ?array => $this->getMenuItem($child))
            ->filter()
            ->values()
            ->toArray();

        if ($item->children->count() > 0 && $children === []) {
            return null;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'link' => $children !== [] ? null : $link ?? null,
            'external' => $external ?? true,
            'children' => $children,
        ];
    }

    /**
     * @return array{id: string, name: string, link: string, external: bool, children: array<int, array<string, mixed>>}
     */
    private function calendarMenuItem(): array
    {
        return [
            'id' => 'calendar',
            'name' => 'Calendar',
            'link' => route('calendar'),
            'external' => false,
            'children' => [],
        ];
    }

    private function newsMenuItem(): array
    {
        return [
            'id' => 'news',
            'name' => 'News',
            'link' => route('news.index'),
            'external' => false,
            'children' => [],
        ];
    }

    /**
     * @return array{id: string, name: string, link: null, external: bool, children: array<int, array{id: string, name: string, link: string, external: bool, children: array<int, mixed>}>}
     */
    private function toolsMenuItem(): array
    {
        return [
            'id' => 'tools',
            'name' => 'Tools',
            'link' => null,
            'external' => false,
            'children' => [
                [
                    'id' => 'tools-projects',
                    'name' => 'Projects',
                    'link' => route('tools.projects'),
                    'external' => false,
                    'children' => [],
                ],
                [
                    'id' => 'tools-meal-planner',
                    'name' => 'Meal planner',
                    'link' => route('meal-planner'),
                    'external' => false,
                    'children' => [],
                ],
            ],
        ];
    }

    private function insertUtilityItemsAfterSections(Collection $menuItems): Collection
    {
        $utilityItems = collect([
            $this->newsMenuItem(),
            $this->toolsMenuItem(),
        ]);

        if ($menuItems->contains(fn (array $item): bool => $item['id'] === 'news' || $item['id'] === 'tools')) {
            return $menuItems->values();
        }

        $sectionsIndex = $menuItems->search(fn (array $item): bool => $item['name'] === 'Sections');

        if ($sectionsIndex === false) {
            return $menuItems
                ->concat($utilityItems)
                ->values();
        }

        return $menuItems
            ->take($sectionsIndex + 1)
            ->concat($utilityItems)
            ->concat($menuItems->slice($sectionsIndex + 1))
            ->values();
    }

    private function moveContactToEnd(Collection $menuItems): Collection
    {
        $contactItem = $menuItems->first(fn (array $item): bool => $item['name'] === 'Contact');

        if (! is_array($contactItem)) {
            return $menuItems->values();
        }

        return $menuItems
            ->reject(fn (array $item): bool => $item['name'] === 'Contact')
            ->push($contactItem)
            ->values();
    }

    private function isDisabledSectionPageMenuItem(MenuItem $item): bool
    {
        if ($item->type !== MenuItemType::MODEL || ! $item->menuable instanceof Page) {
            return false;
        }

        $section = Section::fromSlug($item->menuable->slug);

        return $section instanceof Section && ! app(SectionSettings::class)->isEnabled($section);
    }
}
