<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Models\FoodItem;
use App\Models\Meal;
use App\Models\Project;
use Inertia\Inertia;
use InertiaUI\Modal\Modal;

class AddMealFoodItemModalController extends Controller
{
    public function __invoke(Project $project, Meal $meal): Modal
    {
        abort_unless($meal->project_id === $project->id, 404);

        $this->authorize('update', $project);

        return Inertia::modal('Tools/MealPlanner/AddFoodItemModal', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'people_count' => $project->people_count,
            ],
            'meal' => [
                'id' => $meal->id,
                'name' => $meal->name,
                'meal_type' => $meal->meal_type->value,
                'day_number' => $meal->day_number,
            ],
            'foodSearch' => $this->foodSearchPayload(),
        ])->baseRoute('meal-planner.show', $project);
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
                'latest_priced_at' => $foodItem->latestPrice?->priced_at?->toDateString(),
            ])
            ->all();
    }
}
