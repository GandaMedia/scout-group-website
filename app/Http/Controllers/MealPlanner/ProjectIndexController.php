<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\MealPlanner\MealPlannerTotals;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectIndexController extends Controller
{
    public function __invoke(Request $request, MealPlannerTotals $totals): Response
    {
        $projects = Project::query()
            ->whereBelongsTo($request->user())
            ->with(['meals.mealFoodItems'])
            ->latest('event_date')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'people_count' => $project->people_count,
                'event_date' => $project->event_date->toDateString(),
                'totals' => $totals->forProject($project),
            ]);

        return Inertia::render('Tools/Projects/Index', [
            'projects' => $projects,
        ]);
    }
}
