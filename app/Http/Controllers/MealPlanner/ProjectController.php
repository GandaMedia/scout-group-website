<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlanner\StoreProjectRequest;
use App\Models\Project;
use App\Services\MealPlanner\ProjectSnapshotter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $project = Project::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $snapshotter->snapshot($project, $request->user(), 'project_created');

        return to_route('tools.projects');
    }

    public function update(StoreProjectRequest $request, Project $project, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());
        $snapshotter->snapshot($project, $request->user(), 'project_updated');

        return back();
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return to_route('tools.projects');
    }

    public function duplicate(Request $request, Project $project, ProjectSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorize('duplicate', $project);

        $copy = DB::transaction(function () use ($project, $request, $snapshotter): Project {
            $project->load(['meals.mealFoodItems']);

            $copy = $project->replicate();
            $copy->name = $project->name.' copy';
            $copy->user_id = $request->user()->id;
            $copy->save();

            foreach ($project->meals as $meal) {
                $mealCopy = $meal->replicate();
                $mealCopy->project_id = $copy->id;
                $mealCopy->save();

                foreach ($meal->mealFoodItems as $mealFoodItem) {
                    $lineCopy = $mealFoodItem->replicate();
                    $lineCopy->meal_id = $mealCopy->id;
                    $lineCopy->save();
                }
            }

            $snapshotter->snapshot($copy, $request->user(), 'project_duplicated');

            return $copy;
        });

        return to_route('tools.projects');
    }
}
