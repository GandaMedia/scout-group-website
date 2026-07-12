<?php

namespace App\Services\MealPlanner;

use App\Models\Meal;
use App\Models\Project;
use Illuminate\Support\Str;

class MealPlannerExportData
{
    public function __construct(
        private readonly MealPlannerTotals $totals,
    ) {}

    /**
     * @return array{
     *     project: array{id: int, name: string, people_count: int, event_date: string, totals: array<string, int>},
     *     meals: list<array{id: int, name: string, meal_type: string, day_number: int|null, totals: array<string, int>, lines: list<array<string, mixed>>}>,
     *     generated_at: string,
     * }
     */
    public function forProject(Project $project): array
    {
        $project->load([
            'meals' => fn ($query) => $query
                ->with(['mealFoodItems.foodItem.brand', 'mealFoodItems.foodItem.store', 'mealFoodItems.foodItem.latestPrice'])
                ->orderByRaw('day_number is null, day_number asc')
                ->orderBy('meal_type')
                ->orderBy('name'),
        ]);

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'people_count' => $project->people_count,
                'event_date' => $project->event_date->toDateString(),
                'totals' => $this->totals->forProject($project),
            ],
            'meals' => $project->meals
                ->map(fn (Meal $meal): array => [
                    'id' => $meal->id,
                    'name' => $meal->name,
                    'meal_type' => $meal->meal_type->value,
                    'day_number' => $meal->day_number,
                    'totals' => $this->totals->forMeal($meal, $project->people_count),
                    'lines' => $this->totals->mealLines($meal->mealFoodItems, $project->people_count)->values()->all(),
                ])
                ->values()
                ->all(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function filename(Project $project, string $extension): string
    {
        $base = Str::of($project->name)
            ->ascii()
            ->slug()
            ->whenEmpty(fn (): string => 'project')
            ->append('-meal-planner');

        return "{$base}.{$extension}";
    }
}
