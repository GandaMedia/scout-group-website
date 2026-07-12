<?php

namespace App\Jobs;

use App\Services\WaitingList\Osm\OsmDirectoryService;
use App\Settings\OsmSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;
use Saloon\RateLimitPlugin\Helpers\ApiRateLimited;
use Throwable;

class RefreshOsmDirectorySnapshot implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function middleware(): array
    {
        return [
            new ApiRateLimited,
        ];
    }

    public function handle(OsmDirectoryService $osmDirectoryService, OsmSettings $osmSettings): void
    {
        try {
            $directory = $osmDirectoryService->fetchDirectory();

            $osmSettings->storeDirectorySnapshot(
                accountName: $directory['account_name'],
                accountEmail: $directory['account_email'],
                sections: $directory['sections'],
                termsBySection: $directory['terms_by_section'],
            );
        } catch (RateLimitReachedException $exception) {
            $osmSettings->markDirectoryRefreshQueued();

            throw $exception;
        } catch (Throwable $throwable) {
            $osmSettings->markDirectoryRefreshFailed($throwable->getMessage());

            if ($throwable instanceof FatalRequestException || $throwable instanceof RequestException) {
                throw $throwable;
            }
        }
    }

    public function failed(Throwable $throwable): void
    {
        Log::warning('OSM directory refresh job failed.', [
            'message' => $throwable->getMessage(),
        ]);
    }
}
