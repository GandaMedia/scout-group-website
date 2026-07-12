<?php

namespace App\Http\Requests\MealPlanner;

use App\Models\FoodPrice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMealFoodItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'food_item_id' => ['required', 'integer', Rule::exists('food_items', 'id')->whereNull('deleted_at')],
            'food_price_id' => ['required', 'integer', Rule::exists('food_prices', 'id')],
            'amount_per_serving' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
        ];
    }

    public function foodPrice(): FoodPrice
    {
        return FoodPrice::query()
            ->where('food_item_id', $this->integer('food_item_id'))
            ->findOrFail($this->integer('food_price_id'));
    }
}
