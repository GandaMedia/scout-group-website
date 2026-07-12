<?php

namespace Database\Factories;

use App\Models\FoodPrice;
use App\Models\Meal;
use App\Models\MealFoodItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealFoodItem>
 */
class MealFoodItemFactory extends Factory
{
    protected $model = MealFoodItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $foodPrice = FoodPrice::factory()->create();

        return [
            'meal_id' => Meal::factory(),
            'food_item_id' => $foodPrice->food_item_id,
            'food_price_id' => $foodPrice->id,
            'amount_per_serving' => $this->faker->randomFloat(2, 0.25, 4),
            'price_per_pack' => $foodPrice->price_per_pack,
            'servings_per_pack' => $foodPrice->foodItem->servings_per_pack,
            'calories_per_pack' => $foodPrice->foodItem->calories_per_pack,
            'priced_at' => $foodPrice->priced_at,
        ];
    }
}
