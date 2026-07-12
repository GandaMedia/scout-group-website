<?php

namespace Database\Factories;

use App\Enums\Section;
use App\Models\Leader;
use App\Models\LeaderSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaderSection>
 */
class LeaderSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leader_id' => Leader::factory(),
            'section' => fake()->randomElement(Section::cases()),
        ];
    }

    public function section(Section $section): static
    {
        return $this->state(fn (): array => [
            'section' => $section,
        ]);
    }
}
