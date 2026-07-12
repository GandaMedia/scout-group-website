<?php

namespace App\Console\Commands;

use App\Jobs\SyncWaitingListEntry;
use App\Models\WaitingListEntry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('waiting-list:sync')]
#[Description('Dispatch sync jobs for pending waiting-list entries')]
class SyncWaitingListEntriesCommand extends Command
{
    public function handle(): int
    {
        $entries = WaitingListEntry::query()
            ->eligibleForSync()
            ->orderBy('submitted_at')
            ->limit(10)
            ->get();

        foreach ($entries as $entry) {
            $entry->queueForSync();

            SyncWaitingListEntry::dispatch($entry->getKey());
        }

        $this->info("Queued {$entries->count()} waiting-list entr".($entries->count() === 1 ? 'y' : 'ies').' for OSM sync.');

        return self::SUCCESS;
    }
}
