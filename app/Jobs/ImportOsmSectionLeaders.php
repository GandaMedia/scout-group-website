<?php

namespace App\Jobs;

use App\Services\Leaders\ImportOsmSectionLeaders as ImportOsmSectionLeadersService;
use App\Settings\OsmSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;
use Saloon\RateLimitPlugin\Helpers\ApiRateLimited;
use Throwable;

class ImportOsmSectionLeaders implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function middleware(): array
    {
        return [
            new ApiRateLimited,
        ];
    }

    public function handle(ImportOsmSectionLeadersService $importOsmSectionLeaders, OsmSettings $osmSettings): void
    {
        try {
            $result = $importOsmSectionLeaders($osmSettings);

            Log::info('OSM section leader import completed.', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'checked' => $result['checked'],
                'failed_sections' => $result['failed_sections'],
            ]);
        } catch (RateLimitReachedException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            Log::warning('OSM section leader import failed.', [
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $throwable): void
    {
        Log::warning('OSM section leader import job failed.', [
            'message' => $throwable->getMessage(),
        ]);
    }
}
