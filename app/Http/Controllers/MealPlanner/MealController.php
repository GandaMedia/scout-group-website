<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlanner\StoreMealRequest;
use App\Models\Meal;
use App\Models\Project;
use App\Services\MealPlanner\ProjectSnapshotter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MealController extends Controller
{
    public function store(StoreMealRequest $request, Project $project, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->meals()->create($request->validated());
        $snapshotter->snapshot($project, $request->user(), 'meal_created');

        return back();
    }

    public function update(StoreMealRequest $request, Project $project, Meal $meal, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorizeMeal($project, $meal);

        $meal->update($request->validated());
        $snapshotter->snapshot($project, $request->user(), 'meal_updated');

        return back();
    }

    public function destroy(Project $project, Meal $meal, ProjectSnapshotter $snapshotter, Request $request): RedirectResponse
    {
        $this->authorizeMeal($project, $meal);

        $meal->delete();
        $snapshotter->snapshot($project, $request->user(), 'meal_deleted');

        return back();
    }

    public function duplicate(Request $request, Project $project, Meal $meal, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorizeMeal($project, $meal);

        DB::transaction(function () use ($meal, $project, $snapshotter, $request): void {
            $meal->load('mealFoodItems');

            $copy = $meal->replicate();
            $copy->name = $meal->name.' copy';
            $copy->project_id = $project->id;
            $copy->save();

            foreach ($meal->mealFoodItems as $mealFoodItem) {
                $lineCopy = $mealFoodItem->replicate();
                $lineCopy->meal_id = $copy->id;
                $lineCopy->save();
            }

            $snapshotter->snapshot($project, $request->user(), 'meal_duplicated');
        });

        return back();
    }

    public function refreshPrices(Request $request, Project $project, Meal $meal, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorizeMeal($project, $meal);

        $meal->load('mealFoodItems.foodItem.latestPrice.foodItem');

        foreach ($meal->mealFoodItems as $mealFoodItem) {
            $latestPrice = $mealFoodItem->foodItem->latestPrice;

            if ($latestPrice !== null && $mealFoodItem->isStale()) {
                $mealFoodItem->refreshPriceSnapshot($latestPrice);
            }
        }

        $snapshotter->snapshot($project, $request->user(), 'meal_prices_refreshed');

        return back();
    }

    private function authorizeMeal(Project $project, Meal $meal): void
    {
        abort_unless($meal->project_id === $project->id, 404);

        $this->authorize('update', $project);
    }
}
