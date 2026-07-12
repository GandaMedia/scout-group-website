<?php

namespace Database\Factories;

use App\Enums\Section;
use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addHour()->toDateTimeString(),
            'content' => $this->faker->sentence(),
            'all_day' => $this->faker->boolean(),
            'is_manual' => true,
            'sections' => $this->faker->randomElements(
                Section::cases(),
                $this->faker->numberBetween(1, count(Section::cases())),
            ),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
