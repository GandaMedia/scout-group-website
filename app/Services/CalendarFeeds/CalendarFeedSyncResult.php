<?php

namespace App\Services\CalendarFeeds;

readonly class CalendarFeedSyncResult
{
    public function __construct(
        public int $sourcesProcessed,
        public int $sourcesFailed,
        public int $eventsImported,
        public int $eventsRemoved,
    ) {}
}
