<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectCostSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectCostSnapshot>
 */
class ProjectCostSnapshotFactory extends Factory
{
    protected $model = ProjectCostSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'created_by_user_id' => User::factory(),
            'total_cost' => $this->faker->numberBetween(0, 100000),
            'cost_per_head' => $this->faker->numberBetween(0, 10000),
            'total_calories_per_serving' => $this->faker->numberBetween(0, 10000),
            'meal_count' => $this->faker->numberBetween(0, 20),
            'snapshot_reason' => $this->faker->word(),
        ];
    }
}
