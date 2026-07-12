<?php

namespace Database\Factories;

use App\Models\FoodItem;
use App\Models\FoodPrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodPrice>
 */
class FoodPriceFactory extends Factory
{
    protected $model = FoodPrice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'food_item_id' => FoodItem::factory(),
            'price_per_pack' => $this->faker->numberBetween(50, 5000),
            'priced_at' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'created_by_user_id' => User::factory(),
        ];
    }
}
