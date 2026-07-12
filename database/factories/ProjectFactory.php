<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'people_count' => $this->faker->numberBetween(1, 120),
            'event_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'user_id' => User::factory(),
        ];
    }
}
