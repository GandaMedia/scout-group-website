<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\MealPlanner\MealPlannerExcelExporter;
use App\Services\MealPlanner\MealPlannerExportData;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectExcelExportController extends Controller
{
    public function __invoke(
        Project $project,
        MealPlannerExportData $exportData,
        MealPlannerExcelExporter $excelExporter,
    ): StreamedResponse {
        $this->authorize('view', $project);

        $filename = $exportData->filename($project, 'xlsx');
        $export = $exportData->forProject($project);

        return response()->streamDownload(
            fn () => $excelExporter->writeToStream($export),
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }
}
