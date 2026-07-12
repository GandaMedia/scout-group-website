<?php

use App\Enums\MealType;
use App\Models\Brand;
use App\Models\FoodItem;
use App\Models\FoodPrice;
use App\Models\Meal;
use App\Models\MealFoodItem;
use App\Models\Project;
use App\Models\ProjectCostSnapshot;
use App\Models\Store;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use OpenSpout\Reader\XLSX\Reader;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\Permission\Models\Permission;

function createMealPlannerLeader(): User
{
    Permission::findOrCreate('access leader tools');

    $user = User::factory()->create();
    $user->givePermissionTo('access leader tools');

    return $user;
}

test('guests and unverified users cannot use the meal planner', function () {
    $this->get(route('meal-planner'))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('tools.projects'))
        ->assertRedirect(route('verification.notice'));
});

test('verified users can create projects and cannot update another users project', function () {
    $user = createMealPlannerLeader();
    $otherProject = Project::factory()->create();

    $this->actingAs($user)
        ->post(route('tools.projects.store'), [
            'name' => 'Summer camp',
            'people_count' => 24,
            'event_date' => '2026-08-01',
        ])
        ->assertRedirect();

    $project = Project::query()->where('name', 'Summer camp')->firstOrFail();

    expect($project->user_id)->toBe($user->id)
        ->and(ProjectCostSnapshot::query()->whereBelongsTo($project)->exists())->toBeTrue();

    $this->actingAs($user)
        ->patch(route('tools.projects.update', $otherProject), [
            'name' => 'Nope',
            'people_count' => 4,
            'event_date' => '2026-08-01',
        ])
        ->assertForbidden();
});

test('verified users manage projects separately from the selected project meal planner', function () {
    $user = createMealPlannerLeader();
    $project = Project::factory()->for($user)->create(['name' => 'Winter camp']);
    $otherProject = Project::factory()->create();

    $this->actingAs($user)
        ->get(route('tools.projects'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tools/Projects/Index')
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
        );

    $this->actingAs($user)
        ->get(route('meal-planner.show', $project))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tools/MealPlanner/Index')
            ->where('project.id', $project->id)
        );

    $this->actingAs($user)
        ->get(route('meal-planner.show', $otherProject))
        ->assertForbidden();
});

test('project cost history is loaded from a scoped modal route', function () {
    $user = createMealPlannerLeader();
    $project = Project::factory()->for($user)->create(['name' => 'Summer camp']);
    $otherProject = Project::factory()->create();

    ProjectCostSnapshot::factory()->for($project)->create([
        'total_cost' => 12345,
        'cost_per_head' => 1234,
        'snapshot_reason' => 'meal_created',
    ]);

    $this->actingAs($user)
        ->get(route('meal-planner.show', $project))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tools/MealPlanner/Index')
            ->missing('project.costSnapshots')
        );

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'test-modal'])
        ->get(route('meal-planner.cost-history', $project))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tools/MealPlanner/CostHistoryModal')
            ->where('project.id', $project->id)
            ->where('snapshots.0.total_cost_minor', 12345)
            ->where('snapshots.0.cost_per_head_minor', 1234)
            ->where('snapshots.0.snapshot_reason', 'meal_created')
        );

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'test-modal'])
        ->get(route('meal-planner.cost-history', $otherProject))
        ->assertForbidden();
});

test('meal planner exports require verified project ownership', function () {
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $unverifiedUser = User::factory()->unverified()->create();

    $this->get(route('meal-planner.exports.pdf', $project))
        ->assertRedirect(route('login'));

    $this->get(route('meal-planner.exports.excel', $project))
        ->assertRedirect(route('login'));

    $this->actingAs($unverifiedUser)
        ->get(route('meal-planner.exports.pdf', $project))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($project->user)
        ->get(route('meal-planner.exports.pdf', $otherProject))
        ->assertForbidden();

    $this->actingAs($project->user)
        ->get(route('meal-planner.exports.excel', $otherProject))
        ->assertForbidden();
});

test('project owner can download a meal planner pdf using saved line snapshots', function () {
    Pdf::fake();

    [$project, $food] = mealPlannerExportScenario();

    $this->actingAs($project->user)
        ->get(route('meal-planner.exports.pdf', $project))
        ->assertSuccessful();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($project, $food): bool {
        return $pdf->viewName === 'pdfs.meal-planner.project'
            && $pdf->downloadName === 'summer-camp-meal-planner.pdf'
            && $pdf->isDownload()
            && $pdf->contains([$project->name, $food->name, '£3.00'])
            && ! $pdf->contains('£4.50');
    });
});

test('project owner can download a meal planner excel workbook using saved line snapshots', function () {
    [$project, $food] = mealPlannerExportScenario();

    $response = $this->actingAs($project->user)
        ->get(route('meal-planner.exports.excel', $project))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $contents = $response->streamedContent();

    expect(substr($contents, 0, 2))->toBe('PK')
        ->and(strlen($contents))->toBeGreaterThan(1000);

    $path = tempnam(sys_get_temp_dir(), 'meal-planner-export-');
    file_put_contents($path, $contents);

    try {
        $reader = new Reader;
        $reader->open($path);

        $sheets = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $rows = [];

            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }

            $sheets[$sheet->getName()] = $rows;
        }

        $reader->close();
    } finally {
        File::delete($path);
    }

    expect($sheets['Summary'][1])->toBe(['Project', 'Summer camp'])
        ->and($sheets['Summary'][4])->toBe(['Total cost', 6])
        ->and($sheets['Meal lines'][1][3])->toBe($food->name)
        ->and($sheets['Meal lines'][1][8])->toBe(3)
        ->and($sheets['Meal lines'][1][11])->toBe(6);
});

test('meal food item modal routes are scoped to the selected project', function () {
    $user = createMealPlannerLeader();
    $project = Project::factory()->for($user)->create();
    $meal = Meal::factory()->for($project)->create(['name' => 'Breakfast']);
    $otherMeal = Meal::factory()->create();

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'test-modal'])
        ->get(route('meal-planner.meal-food-items.create', [$project, $meal]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tools/MealPlanner/AddFoodItemModal')
            ->where('meal.id', $meal->id)
        );

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'test-modal'])
        ->get(route('meal-planner.food-items.create', [$project, $meal]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tools/MealPlanner/CreateFoodItemModal')
            ->where('project.id', $project->id)
        );

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'test-modal'])
        ->get(route('meal-planner.meal-food-items.create', [$project, $otherMeal]))
        ->assertNotFound();
});

function mealPlannerExportScenario(): array
{
    $user = createMealPlannerLeader();
    $project = Project::factory()->for($user)->create([
        'name' => 'Summer camp',
        'people_count' => 12,
        'event_date' => '2026-08-01',
    ]);
    $meal = Meal::factory()->for($project)->create([
        'name' => 'Breakfast',
        'meal_type' => MealType::BREAKFAST,
        'day_number' => 1,
    ]);
    $brand = Brand::factory()->create(['name' => 'Heinz']);
    $store = Store::factory()->create(['name' => 'Tesco']);
    $food = FoodItem::factory()->create([
        'name' => 'Beans',
        'brand_id' => $brand->id,
        'store_id' => $store->id,
        'servings_per_pack' => 6,
        'calories_per_pack' => 900,
    ]);
    $savedPrice = FoodPrice::factory()->create([
        'food_item_id' => $food->id,
        'price_per_pack' => 300,
        'priced_at' => '2026-06-01',
    ]);
    FoodPrice::factory()->create([
        'food_item_id' => $food->id,
        'price_per_pack' => 450,
        'priced_at' => '2026-06-29',
    ]);
    MealFoodItem::factory()->for($meal)->create([
        'food_item_id' => $food->id,
        'food_price_id' => $savedPrice->id,
        'amount_per_serving' => '1.00',
        'price_per_pack' => 300,
        'servings_per_pack' => 6,
        'calories_per_pack' => 900,
        'priced_at' => '2026-06-01',
    ]);

    return [$project, $food];
}

test('meal food item modals are not blocked by stale build versions while vite is hot', function () {
    $hotPath = public_path('hot');
    $hadHotFile = File::exists($hotPath);
    $hotContents = $hadHotFile ? File::get($hotPath) : null;

    try {
        File::put($hotPath, 'https://scout-group-website.test:5173');

        $user = createMealPlannerLeader();
        $project = Project::factory()->for($user)->create();
        $meal = Meal::factory()->for($project)->create(['name' => 'Breakfast']);

        $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => 'stale-version',
                'X-InertiaUI-Modal' => 'test-modal',
            ])
            ->get(route('meal-planner.meal-food-items.create', [$project, $meal]))
            ->assertSuccessful()
            ->assertHeaderMissing('X-Inertia-Location');
    } finally {
        if ($hadHotFile && is_string($hotContents)) {
            File::put($hotPath, $hotContents);
        } else {
            File::delete($hotPath);
        }
    }
});

test('food search uses the shared catalog and includes brand and store terms', function () {
    $user = createMealPlannerLeader();
    $brand = Brand::factory()->create(['name' => 'Free Range Co']);
    $store = Store::factory()->create(['name' => 'Tesco']);
    $food = FoodItem::factory()->create([
        'name' => 'Eggs',
        'brand_id' => $brand->id,
        'store_id' => $store->id,
        'created_by_user_id' => $user->id,
    ]);
    FoodPrice::factory()->create([
        'food_item_id' => $food->id,
        'price_per_pack' => 240,
        'priced_at' => '2026-06-01',
    ]);

    $this->actingAs($user)
        ->getJson(route('meal-planner.food-items.index', ['q' => 'Tesco']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $food->id);
});

test('catalog food and price endpoints can return json for nested modal saves', function () {
    $user = createMealPlannerLeader();

    $brand = $this->actingAs($user)
        ->postJson(route('meal-planner.catalog-options.store'), [
            'type' => 'brand',
            'name' => 'Camp Kitchen',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Camp Kitchen')
        ->json('data');

    $store = $this->actingAs($user)
        ->postJson(route('meal-planner.catalog-options.store'), [
            'type' => 'store',
            'name' => 'Booker',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Booker')
        ->json('data');

    $food = $this->actingAs($user)
        ->postJson(route('meal-planner.food-items.store'), [
            'name' => 'Beans',
            'brand_id' => $brand['id'],
            'store_id' => $store['id'],
            'servings_per_pack' => 6,
            'calories_per_pack' => 900,
            'price_per_pack' => 350,
            'priced_at' => '2026-06-29',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Beans')
        ->assertJsonPath('data.latest_price_minor', 350)
        ->json('data');

    $this->actingAs($user)
        ->postJson(route('meal-planner.food-prices.store', $food['id']), [
            'price_per_pack' => 375,
            'priced_at' => '2026-07-01',
        ])
        ->assertCreated()
        ->assertJsonPath('data.price_per_pack_minor', 375);
});

test('meal lines snapshot prices and refresh stale prices explicitly', function () {
    $user = createMealPlannerLeader();
    $project = Project::factory()->for($user)->create(['people_count' => 12]);
    $meal = Meal::factory()->for($project)->create(['meal_type' => MealType::BREAKFAST]);
    $food = FoodItem::factory()->create([
        'servings_per_pack' => 6,
        'calories_per_pack' => 900,
    ]);
    $oldPrice = FoodPrice::factory()->create([
        'food_item_id' => $food->id,
        'price_per_pack' => 300,
        'priced_at' => '2026-06-01',
    ]);

    $this->actingAs($user)
        ->post(route('meal-planner.meal-food-items.store', [$project, $meal]), [
            'food_item_id' => $food->id,
            'food_price_id' => $oldPrice->id,
            'amount_per_serving' => '1.00',
        ])
        ->assertRedirect();

    $line = MealFoodItem::query()->firstOrFail();

    expect($line->price_per_pack)->toBeInstanceOf(Money::class)
        ->and($line->price_per_pack->getMinorAmount()->toInt())->toBe(300)
        ->and($line->packsRequired(12))->toBe(2)
        ->and($line->totalCostMinor(12))->toBe(600);

    $newPrice = FoodPrice::factory()->create([
        'food_item_id' => $food->id,
        'price_per_pack' => 420,
        'priced_at' => '2026-06-15',
    ]);

    expect($line->fresh(['foodItem.latestPrice'])->isStale())->toBeTrue();

    $this->actingAs($user)
        ->post(route('meal-planner.meals.refresh-prices', [$project, $meal]))
        ->assertRedirect();

    $line->refresh();

    expect($line->food_price_id)->toBe($newPrice->id)
        ->and($line->price_per_pack->getMinorAmount()->toInt())->toBe(420)
        ->and(ProjectCostSnapshot::query()->where('snapshot_reason', 'meal_prices_refreshed')->exists())->toBeTrue();
});

test('line price updates create a catalog price and immediately update the meal line snapshot', function () {
    $user = createMealPlannerLeader();
    $project = Project::factory()->for($user)->create(['people_count' => 12]);
    $meal = Meal::factory()->for($project)->create();
    $food = FoodItem::factory()->create([
        'servings_per_pack' => 6,
        'calories_per_pack' => 900,
    ]);
    $oldPrice = FoodPrice::factory()->create([
        'food_item_id' => $food->id,
        'price_per_pack' => 300,
        'priced_at' => '2026-06-01',
    ]);
    $line = MealFoodItem::factory()->for($meal)->create([
        'food_item_id' => $food->id,
        'food_price_id' => $oldPrice->id,
        'amount_per_serving' => '1.00',
        'price_per_pack' => 300,
        'servings_per_pack' => 6,
        'calories_per_pack' => 900,
        'priced_at' => '2026-06-01',
    ]);

    $this->actingAs($user)
        ->post(route('meal-planner.food-prices.store', $food), [
            'price_per_pack' => 450,
            'priced_at' => '2026-06-29',
            'meal_food_item_id' => $line->id,
        ])
        ->assertRedirect();

    $line->refresh();

    expect($line->food_price_id)->not->toBe($oldPrice->id)
        ->and($line->price_per_pack->getMinorAmount()->toInt())->toBe(450)
        ->and($line->priced_at->toDateString())->toBe('2026-06-29')
        ->and($line->totalCostMinor(12))->toBe(900)
        ->and(ProjectCostSnapshot::query()->whereBelongsTo($project)->where('snapshot_reason', 'meal_line_price_updated')->exists())->toBeTrue();
});

test('project duplication preserves saved meal line snapshots', function () {
    $user = createMealPlannerLeader();
    $project = Project::factory()->for($user)->create();
    $meal = Meal::factory()->for($project)->create();
    $line = MealFoodItem::factory()->for($meal)->create(['price_per_pack' => 199]);

    $this->actingAs($user)
        ->post(route('tools.projects.duplicate', $project))
        ->assertRedirect();

    $copiedProject = Project::query()
        ->where('id', '!=', $project->id)
        ->whereBelongsTo($user)
        ->firstOrFail();

    $copiedLine = $copiedProject->meals()->firstOrFail()->mealFoodItems()->firstOrFail();

    expect($copiedLine->food_item_id)->toBe($line->food_item_id)
        ->and($copiedLine->price_per_pack->getMinorAmount()->toInt())->toBe(199);
});
