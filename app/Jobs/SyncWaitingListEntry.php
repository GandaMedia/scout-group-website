<?php

namespace App\Jobs;

use App\Models\WaitingListEntry;
use App\Services\WaitingList\OsmWaitingListService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;
use Saloon\RateLimitPlugin\Helpers\ApiRateLimited;
use Throwable;

class SyncWaitingListEntry implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $waitingListEntryId) {}

    public function middleware(): array
    {
        return [
            new ApiRateLimited,
        ];
    }

    public function handle(OsmWaitingListService $osmWaitingListService): void
    {
        $entry = WaitingListEntry::query()->find($this->waitingListEntryId);

        if (! $entry instanceof WaitingListEntry) {
            return;
        }

        if (in_array($entry->sync_status->value, ['held_duplicate', 'synced'], true)) {
            return;
        }

        try {
            $payload = $osmWaitingListService->buildPayload($entry);
            $entry->markSyncing($payload);

            $result = $osmWaitingListService->sync($entry, $payload);

            $entry->markSynced(
                scoutId: (int) $result['scoutid'],
                response: $result,
            );
        } catch (RateLimitReachedException $exception) {
            $entry->queueForSync();

            throw $exception;
        } catch (Throwable $throwable) {
            $entry->markFailed($throwable->getMessage());

            if ($throwable instanceof FatalRequestException
                || $throwable instanceof RequestException) {
                throw $throwable;
            }
        }
    }

    public function failed(Throwable $throwable): void
    {
        Log::warning('Waiting-list OSM sync job failed.', [
            'waiting_list_entry_id' => $this->waitingListEntryId,
            'message' => $throwable->getMessage(),
        ]);
    }
}
