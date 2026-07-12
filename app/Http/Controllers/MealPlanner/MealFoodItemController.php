<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlanner\StoreMealFoodItemRequest;
use App\Models\FoodPrice;
use App\Models\Meal;
use App\Models\MealFoodItem;
use App\Models\Project;
use App\Services\MealPlanner\ProjectSnapshotter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MealFoodItemController extends Controller
{
    public function store(StoreMealFoodItemRequest $request, Project $project, Meal $meal, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorizeMeal($project, $meal);

        $foodPrice = $request->foodPrice()->load('foodItem');

        $meal->mealFoodItems()->create([
            'food_item_id' => $foodPrice->food_item_id,
            'food_price_id' => $foodPrice->id,
            'amount_per_serving' => $request->validated('amount_per_serving'),
            ...$this->snapshotAttributes($foodPrice),
        ]);

        $snapshotter->snapshot($project, $request->user(), 'meal_line_created');

        return back();
    }

    public function update(StoreMealFoodItemRequest $request, Project $project, Meal $meal, MealFoodItem $mealFoodItem, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorizeLine($project, $meal, $mealFoodItem);

        $foodPrice = $request->foodPrice()->load('foodItem');

        $mealFoodItem->update([
            'food_item_id' => $foodPrice->food_item_id,
            'food_price_id' => $foodPrice->id,
            'amount_per_serving' => $request->validated('amount_per_serving'),
            ...$this->snapshotAttributes($foodPrice),
        ]);

        $snapshotter->snapshot($project, $request->user(), 'meal_line_updated');

        return back();
    }

    public function destroy(Request $request, Project $project, Meal $meal, MealFoodItem $mealFoodItem, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorizeLine($project, $meal, $mealFoodItem);

        $mealFoodItem->delete();
        $snapshotter->snapshot($project, $request->user(), 'meal_line_deleted');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotAttributes(FoodPrice $foodPrice): array
    {
        return [
            'price_per_pack' => $foodPrice->price_per_pack,
            'servings_per_pack' => $foodPrice->foodItem->servings_per_pack,
            'calories_per_pack' => $foodPrice->foodItem->calories_per_pack,
            'priced_at' => $foodPrice->priced_at,
        ];
    }

    private function authorizeMeal(Project $project, Meal $meal): void
    {
        abort_unless($meal->project_id === $project->id, 404);

        $this->authorize('update', $project);
    }

    private function authorizeLine(Project $project, Meal $meal, MealFoodItem $mealFoodItem): void
    {
        $this->authorizeMeal($project, $meal);
        abort_unless($mealFoodItem->meal_id === $meal->id, 404);
    }
}
