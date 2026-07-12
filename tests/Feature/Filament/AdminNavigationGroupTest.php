<?php

use App\Filament\AdminNavigationGroup;
use App\Filament\Pages\ContactSettings;
use App\Filament\Pages\GroupProfileSettings;
use App\Filament\Pages\HomePageSettings;
use App\Filament\Pages\OsmSettings;
use App\Filament\Pages\SectionSettings;
use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use App\Filament\Resources\CalendarFeedSources\CalendarFeedSourceResource;
use App\Filament\Resources\ContactEnquiries\ContactEnquiryResource;
use App\Filament\Resources\Leaders\LeaderResource;
use App\Filament\Resources\LeaderSections\LeaderSectionResource;
use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Tags\TagResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\WaitingListEntries\WaitingListEntryResource;
use App\Models\User;
use Filament\Support\Contracts\HasIcon;
use Spatie\Permission\Models\Permission;

it('renders the admin dashboard navigation without icon conflicts', function (): void {
    $user = User::factory()->create();

    Permission::create(['name' => 'access admin']);
    $user->givePermissionTo('access admin');

    $this->actingAs($user)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful();
});

it('groups the admin navigation into sensible sections', function (string $navigationClass, AdminNavigationGroup $group, int $sort): void {
    expect($navigationClass::getNavigationGroup())->toBe($group)
        ->and($navigationClass::getNavigationSort())->toBe($sort);
})->with([
    'pages' => [PageResource::class, AdminNavigationGroup::Website, 10],
    'menu items' => [MenuItemResource::class, AdminNavigationGroup::Website, 20],
    'posts' => [PostResource::class, AdminNavigationGroup::Website, 30],
    'tags' => [TagResource::class, AdminNavigationGroup::Website, 40],
    'leaders' => [LeaderResource::class, AdminNavigationGroup::Team, 10],
    'leader sections' => [LeaderSectionResource::class, AdminNavigationGroup::Team, 20],
    'calendar events' => [CalendarEventResource::class, AdminNavigationGroup::Programme, 10],
    'calendar feed sources' => [CalendarFeedSourceResource::class, AdminNavigationGroup::Programme, 20],
    'waiting list' => [WaitingListEntryResource::class, AdminNavigationGroup::Enquiries, 10],
    'contact enquiries' => [ContactEnquiryResource::class, AdminNavigationGroup::Enquiries, 20],
    'group profile settings' => [GroupProfileSettings::class, AdminNavigationGroup::Settings, 10],
    'section settings' => [SectionSettings::class, AdminNavigationGroup::Settings, 20],
    'home page settings' => [HomePageSettings::class, AdminNavigationGroup::Settings, 30],
    'contact settings' => [ContactSettings::class, AdminNavigationGroup::Settings, 40],
    'osm settings' => [OsmSettings::class, AdminNavigationGroup::Settings, 50],
    'users' => [UserResource::class, AdminNavigationGroup::Administration, 10],
]);

it('keeps the admin navigation groups in the intended order', function (): void {
    expect(AdminNavigationGroup::cases())->toBe([
        AdminNavigationGroup::Website,
        AdminNavigationGroup::Team,
        AdminNavigationGroup::Programme,
        AdminNavigationGroup::Enquiries,
        AdminNavigationGroup::Settings,
        AdminNavigationGroup::Administration,
    ]);
});

it('keeps group icons disabled so item icons can render inside each group', function (): void {
    expect(AdminNavigationGroup::Website)->not->toBeInstanceOf(HasIcon::class);
});
