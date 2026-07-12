<?php

namespace App\Services\MealPlanner;

use App\Models\Meal;
use App\Models\MealFoodItem;
use App\Models\Project;
use Illuminate\Support\Collection;

class MealPlannerTotals
{
    /**
     * @return array{total_cost_minor: int, cost_per_head_minor: int, total_calories_per_serving: int, meal_count: int}
     */
    public function forProject(Project $project): array
    {
        $project->loadMissing(['meals.mealFoodItems']);

        $totalCost = $project->meals->sum(
            fn (Meal $meal): int => $this->forMeal($meal, $project->people_count)['total_cost_minor'],
        );

        $totalCalories = $project->meals->sum(
            fn (Meal $meal): int => $this->forMeal($meal, $project->people_count)['calories_per_serving'],
        );

        return [
            'total_cost_minor' => $totalCost,
            'cost_per_head_minor' => $project->people_count > 0 ? (int) round($totalCost / $project->people_count) : 0,
            'total_calories_per_serving' => $totalCalories,
            'meal_count' => $project->meals->count(),
        ];
    }

    /**
     * @return array{total_cost_minor: int, cost_per_serving_minor: int, calories_per_serving: int}
     */
    public function forMeal(Meal $meal, int $peopleCount): array
    {
        $meal->loadMissing('mealFoodItems');

        $totalCost = $meal->mealFoodItems->sum(
            fn ($mealFoodItem): int => $mealFoodItem->totalCostMinor($peopleCount),
        );

        return [
            'total_cost_minor' => $totalCost,
            'cost_per_serving_minor' => $peopleCount > 0 ? (int) round($totalCost / $peopleCount) : 0,
            'calories_per_serving' => $meal->mealFoodItems->sum(fn ($mealFoodItem): int => $mealFoodItem->caloriesPerServing()),
        ];
    }

    /**
     * @param  Collection<int, MealFoodItem>  $mealFoodItems
     * @return Collection<int, array<string, mixed>>
     */
    public function mealLines(Collection $mealFoodItems, int $peopleCount): Collection
    {
        return $mealFoodItems->map(fn ($mealFoodItem): array => [
            'id' => $mealFoodItem->id,
            'food_item_id' => $mealFoodItem->food_item_id,
            'food_price_id' => $mealFoodItem->food_price_id,
            'amount_per_serving' => (float) $mealFoodItem->amount_per_serving,
            'packs_required' => $mealFoodItem->packsRequired($peopleCount),
            'cost_per_serving_minor' => $mealFoodItem->costPerServingMinor(),
            'calories_per_serving' => $mealFoodItem->caloriesPerServing(),
            'total_cost_minor' => $mealFoodItem->totalCostMinor($peopleCount),
            'price_per_pack_minor' => $mealFoodItem->price_per_pack->getMinorAmount()->toInt(),
            'priced_at' => $mealFoodItem->priced_at?->toDateString(),
            'is_stale' => $mealFoodItem->isStale(),
            'food' => [
                'id' => $mealFoodItem->foodItem->id,
                'name' => $mealFoodItem->foodItem->name,
                'brand' => $mealFoodItem->foodItem->brand?->name,
                'store' => $mealFoodItem->foodItem->store?->name,
                'servings_per_pack' => $mealFoodItem->servings_per_pack,
                'calories_per_pack' => $mealFoodItem->calories_per_pack,
                'latest_price_minor' => $mealFoodItem->foodItem->latestPrice?->price_per_pack?->getMinorAmount()->toInt(),
            ],
        ]);
    }
}
