<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\FoodItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodItem>
 */
class FoodItemFactory extends Factory
{
    protected $model = FoodItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'brand_id' => Brand::factory(),
            'store_id' => Store::factory(),
            'servings_per_pack' => $this->faker->numberBetween(1, 24),
            'calories_per_pack' => $this->faker->numberBetween(50, 5000),
            'created_by_user_id' => User::factory(),
        ];
    }
}
