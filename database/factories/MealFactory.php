<?php

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\Meal;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meal>
 */
class MealFactory extends Factory
{
    protected $model = Meal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->words(2, true),
            'meal_type' => $this->faker->randomElement(MealType::cases())->value,
            'day_number' => $this->faker->optional()->numberBetween(1, 7),
        ];
    }
}
