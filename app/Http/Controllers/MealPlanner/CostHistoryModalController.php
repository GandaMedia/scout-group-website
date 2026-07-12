<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCostSnapshot;
use Inertia\Inertia;
use InertiaUI\Modal\Modal;

class CostHistoryModalController extends Controller
{
    public function __invoke(Project $project): Modal
    {
        $this->authorize('view', $project);

        return Inertia::modal('Tools/MealPlanner/CostHistoryModal', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'snapshots' => $project->costSnapshots()
                ->latest()
                ->get()
                ->map(fn (ProjectCostSnapshot $snapshot): array => [
                    'id' => $snapshot->id,
                    'total_cost_minor' => $snapshot->total_cost->getMinorAmount()->toInt(),
                    'cost_per_head_minor' => $snapshot->cost_per_head->getMinorAmount()->toInt(),
                    'total_calories_per_serving' => $snapshot->total_calories_per_serving,
                    'meal_count' => $snapshot->meal_count,
                    'snapshot_reason' => $snapshot->snapshot_reason,
                    'created_at' => $snapshot->created_at?->toIso8601String(),
                ]),
        ])->baseRoute('meal-planner.show', $project);
    }
}
