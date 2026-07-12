<?php

namespace App\Console\Commands;

use App\Services\CalendarFeeds\CalendarFeedSyncService;
use Illuminate\Console\Command;

class SyncCalendarFeedsCommand extends Command
{
    protected $signature = 'calendar:sync-osm-feeds';

    protected $description = 'Sync enabled OSM calendar feeds into the site calendar.';

    public function handle(CalendarFeedSyncService $calendarFeedSyncService): int
    {
        $result = $calendarFeedSyncService->syncAll();

        $this->components->info(sprintf(
            'Processed %d feed(s), %d failed, %d event link(s) imported, %d stale link(s) removed.',
            $result->sourcesProcessed,
            $result->sourcesFailed,
            $result->eventsImported,
            $result->eventsRemoved,
        ));

        return self::SUCCESS;
    }
}
