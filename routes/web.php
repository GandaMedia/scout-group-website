<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\ContactEnquiryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MealPlanner\AddMealFoodItemModalController;
use App\Http\Controllers\MealPlanner\CatalogOptionController;
use App\Http\Controllers\MealPlanner\CostHistoryModalController;
use App\Http\Controllers\MealPlanner\CreateFoodItemModalController;
use App\Http\Controllers\MealPlanner\FoodItemController;
use App\Http\Controllers\MealPlanner\FoodPriceController;
use App\Http\Controllers\MealPlanner\MealController;
use App\Http\Controllers\MealPlanner\MealFoodItemController;
use App\Http\Controllers\MealPlanner\MealPlannerController;
use App\Http\Controllers\MealPlanner\ProjectController;
use App\Http\Controllers\MealPlanner\ProjectExcelExportController;
use App\Http\Controllers\MealPlanner\ProjectIndexController;
use App\Http\Controllers\MealPlanner\ProjectPdfExportController;
use App\Http\Controllers\Osm\OsmOAuthCallbackController;
use App\Http\Controllers\Osm\OsmOAuthRedirectController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WaitingListEntryController;
use App\Http\Controllers\WaitingListPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

Route::get('/calendar/event/{event}', CalendarEventController::class)
    ->name('calendar.events.show');

Route::get('/calendar/{year?}/{month?}', CalendarController::class)
    ->where([
        'year' => '[0-9]{4}',
        'month' => '[0-9]{1,2}',
    ])
    ->name('calendar');

Route::middleware(['auth', 'verified', 'can:access leader tools'])
    ->prefix('tools')
    ->scopeBindings()
    ->group(function (): void {
        Route::get('/projects', ProjectIndexController::class)->name('tools.projects');
        Route::post('/projects', [ProjectController::class, 'store'])->name('tools.projects.store');
        Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('tools.projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('tools.projects.destroy');
        Route::post('/projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('tools.projects.duplicate');

        Route::redirect('/meal-planner', '/tools/projects')->name('meal-planner');

        Route::get('/meal-planner/food-items', [FoodItemController::class, 'index'])->name('meal-planner.food-items.index');
        Route::post('/meal-planner/food-items', [FoodItemController::class, 'store'])->name('meal-planner.food-items.store');
        Route::delete('/meal-planner/food-items/{foodItem}', [FoodItemController::class, 'destroy'])->name('meal-planner.food-items.destroy');
        Route::get('/meal-planner/food-items/{foodItem}/prices', [FoodPriceController::class, 'index'])->name('meal-planner.food-prices.index');
        Route::post('/meal-planner/food-items/{foodItem}/prices', [FoodPriceController::class, 'store'])->name('meal-planner.food-prices.store');

        Route::get('/meal-planner/catalog-options', [CatalogOptionController::class, 'index'])->name('meal-planner.catalog-options.index');
        Route::post('/meal-planner/catalog-options', [CatalogOptionController::class, 'store'])->name('meal-planner.catalog-options.store');

        Route::get('/meal-planner/{project}', MealPlannerController::class)->name('meal-planner.show');
        Route::get('/meal-planner/{project}/exports/pdf', ProjectPdfExportController::class)->name('meal-planner.exports.pdf');
        Route::get('/meal-planner/{project}/exports/excel', ProjectExcelExportController::class)->name('meal-planner.exports.excel');
        Route::get('/meal-planner/{project}/cost-history', CostHistoryModalController::class)->name('meal-planner.cost-history');

        Route::post('/meal-planner/{project}/meals', [MealController::class, 'store'])->name('meal-planner.meals.store');
        Route::patch('/meal-planner/{project}/meals/{meal}', [MealController::class, 'update'])->name('meal-planner.meals.update');
        Route::delete('/meal-planner/{project}/meals/{meal}', [MealController::class, 'destroy'])->name('meal-planner.meals.destroy');
        Route::post('/meal-planner/{project}/meals/{meal}/duplicate', [MealController::class, 'duplicate'])->name('meal-planner.meals.duplicate');
        Route::post('/meal-planner/{project}/meals/{meal}/refresh-prices', [MealController::class, 'refreshPrices'])->name('meal-planner.meals.refresh-prices');

        Route::get('/meal-planner/{project}/meals/{meal}/food-items/create', AddMealFoodItemModalController::class)->name('meal-planner.meal-food-items.create');
        Route::get('/meal-planner/{project}/meals/{meal}/food-items/catalog/create', CreateFoodItemModalController::class)->name('meal-planner.food-items.create');
        Route::post('/meal-planner/{project}/meals/{meal}/food-items', [MealFoodItemController::class, 'store'])->name('meal-planner.meal-food-items.store');
        Route::patch('/meal-planner/{project}/meals/{meal}/food-items/{mealFoodItem}', [MealFoodItemController::class, 'update'])->name('meal-planner.meal-food-items.update');
        Route::delete('/meal-planner/{project}/meals/{meal}/food-items/{mealFoodItem}', [MealFoodItemController::class, 'destroy'])->name('meal-planner.meal-food-items.destroy');
    });
Route::controller(PostController::class)->prefix('news')->name('news.')->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::get('/tag/{tag:slug}', 'tag')->name('tag');
    Route::post('/{post:slug}/unlock', 'unlock')->name('unlock');
    Route::get('/{post:slug}', 'show')->name('show');
});
Route::post('/contact/enquiries', ContactEnquiryController::class)
    ->middleware('throttle:5,1')
    ->name('contact-enquiries.store');

Route::get('/join/{section}', WaitingListPageController::class)
    ->middleware('throttle:20,1')
    ->name('waiting-list.show');
Route::post('/waiting-list/entries', WaitingListEntryController::class)
    ->middleware('throttle:5,1')
    ->name('waiting-list.store');

Route::get('/search', SearchController::class)->name('search');

Route::middleware(['auth', 'verified', 'can:access admin'])->group(function (): void {
    Route::get('/admin/osm/connect', OsmOAuthRedirectController::class)->name('osm.oauth.redirect');
    Route::get('/admin/osm/callback', OsmOAuthCallbackController::class)->name('osm.oauth.callback');
});

Route::get('/{page}', [PageController::class, 'show'])->name('page.show');
