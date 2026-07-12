<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Meal;
use App\Models\Project;
use App\Models\Store;
use Inertia\Inertia;
use InertiaUI\Modal\Modal;

class CreateFoodItemModalController extends Controller
{
    public function __invoke(Project $project, Meal $meal): Modal
    {
        abort_unless($meal->project_id === $project->id, 404);

        $this->authorize('update', $project);

        return Inertia::modal('Tools/MealPlanner/CreateFoodItemModal', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'meal' => [
                'id' => $meal->id,
                'name' => $meal->name,
            ],
            'catalogOptions' => [
                'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
                'stores' => Store::query()->orderBy('name')->get(['id', 'name']),
            ],
        ])->baseRoute('meal-planner.show', $project);
    }
}
