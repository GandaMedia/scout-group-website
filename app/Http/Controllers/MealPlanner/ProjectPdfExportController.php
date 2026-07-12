<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\MealPlanner\MealPlannerExportData;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

class ProjectPdfExportController extends Controller
{
    public function __invoke(Project $project, MealPlannerExportData $exportData)
    {
        $this->authorize('view', $project);

        return Pdf::view('pdfs.meal-planner.project', [
            'export' => $exportData->forProject($project),
        ])
            ->format(Format::A4)
            ->download($exportData->filename($project, 'pdf'));
    }
}
