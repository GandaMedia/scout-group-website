<?php

namespace Database\Factories;

use App\Enums\MenuItemType;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'menu_id' => Menu::factory(),
            'type' => fake()->randomElement(MenuItemType::class)
        ];
    }

    public function withLink(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'link' => fake()->url(),
            ];
        });
    }
}
