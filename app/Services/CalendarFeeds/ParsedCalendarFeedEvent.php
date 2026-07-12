<?php

namespace App\Services\CalendarFeeds;

use App\Enums\Section;
use Carbon\CarbonImmutable;

readonly class ParsedCalendarFeedEvent
{
    public function __construct(
        public int $feedSourceId,
        public Section $section,
        public string $title,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public bool $allDay,
        public ?string $content,
        public string $externalEventKey,
        public ?string $externalEventUid,
        public string $mergeKey,
        public string $sourceFingerprint,
        public string $payloadHash,
    ) {}
}
