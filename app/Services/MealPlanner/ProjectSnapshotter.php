<?php

namespace App\Services\MealPlanner;

use App\Models\Project;
use App\Models\ProjectCostSnapshot;
use App\Models\User;

class ProjectSnapshotter
{
    public function __construct(
        private readonly MealPlannerTotals $totals,
    ) {}

    public function snapshot(Project $project, ?User $user, string $reason): ProjectCostSnapshot
    {
        $totals = $this->totals->forProject($project->fresh(['meals.mealFoodItems']));

        return ProjectCostSnapshot::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $user?->id,
            'total_cost' => $totals['total_cost_minor'],
            'cost_per_head' => $totals['cost_per_head_minor'],
            'total_calories_per_serving' => $totals['total_calories_per_serving'],
            'meal_count' => $totals['meal_count'],
            'snapshot_reason' => $reason,
        ]);
    }
}
