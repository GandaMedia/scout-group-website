<?php

namespace Database\Factories;

use App\Enums\CalendarFeedSyncStatus;
use App\Enums\Section;
use App\Models\CalendarFeedSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalendarFeedSourceFactory extends Factory
{
    protected $model = CalendarFeedSource::class;

    public function definition(): array
    {
        $section = $this->faker->randomElement(Section::cases());

        return [
            'section' => $section,
            'feed_url' => 'https://example.test/'.$section->value.'.ics',
            'is_enabled' => true,
            'last_sync_status' => CalendarFeedSyncStatus::NEVER,
            'last_event_count' => null,
        ];
    }
}
