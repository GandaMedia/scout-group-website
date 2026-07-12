<?php

namespace App\Http\Requests\MealPlanner;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFoodPriceRequest extends FormRequest
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
            'price_per_pack' => ['required', 'integer', 'min:0', 'max:100000000'],
            'priced_at' => ['required', 'date'],
            'meal_food_item_id' => ['nullable', 'integer', 'exists:meal_food_items,id'],
        ];
    }
}
