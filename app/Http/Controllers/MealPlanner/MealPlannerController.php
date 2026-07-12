<?php

namespace App\Http\Controllers\MealPlanner;

use App\Enums\MealType;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\FoodItem;
use App\Models\Meal;
use App\Models\Project;
use App\Models\Store;
use App\Services\MealPlanner\MealPlannerTotals;
use Inertia\Inertia;
use Inertia\Response;

class MealPlannerController extends Controller
{
    public function __invoke(Project $project, MealPlannerTotals $totals): Response
    {
        $this->authorize('view', $project);

        $project->load([
            'meals' => fn ($query) => $query
                ->with(['mealFoodItems.foodItem.brand', 'mealFoodItems.foodItem.store', 'mealFoodItems.foodItem.latestPrice'])
                ->orderByRaw('day_number is null, day_number asc')
                ->orderBy('meal_type')
                ->orderBy('name'),
        ]);

        return Inertia::render('Tools/MealPlanner/Index', [
            'mealTypes' => MealType::options(),
            'project' => $this->projectPayload($project, $totals),
            'foodSearch' => $this->foodSearchPayload(),
            'catalogOptions' => [
                'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
                'stores' => Store::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function projectPayload(Project $project, MealPlannerTotals $totals): array
    {
        $projectTotals = $totals->forProject($project);

        return [
            'id' => $project->id,
            'name' => $project->name,
            'people_count' => $project->people_count,
            'event_date' => $project->event_date->toDateString(),
            'totals' => $projectTotals,
            'meals' => $project->meals->map(fn (Meal $meal): array => [
                'id' => $meal->id,
                'name' => $meal->name,
                'meal_type' => $meal->meal_type->value,
                'day_number' => $meal->day_number,
                'totals' => $totals->forMeal($meal, $project->people_count),
                'has_stale_prices' => $meal->mealFoodItems->contains(fn ($line): bool => $line->isStale()),
                'lines' => $totals->mealLines($meal->mealFoodItems, $project->people_count)->values(),
            ])->values(),
            'audits' => $project->audits()
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($audit): array => [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'user_id' => $audit->user_id,
                    'created_at' => $audit->created_at?->toIso8601String(),
                ]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function foodSearchPayload(): array
    {
        return FoodItem::query()
            ->with(['brand', 'store', 'latestPrice'])
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (FoodItem $foodItem): array => [
                'id' => $foodItem->id,
                'name' => $foodItem->name,
                'brand' => $foodItem->brand?->name,
                'store' => $foodItem->store?->name,
                'servings_per_pack' => $foodItem->servings_per_pack,
                'calories_per_pack' => $foodItem->calories_per_pack,
                'latest_price_id' => $foodItem->latestPrice?->id,
                'latest_price_minor' => $foodItem->latestPrice?->price_per_pack?->getMinorAmount()->toInt(),
            ])
            ->all();
    }
}
