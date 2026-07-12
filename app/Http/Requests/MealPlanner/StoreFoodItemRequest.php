<?php

namespace App\Http\Requests\MealPlanner;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoodItemRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'store_id' => ['required', 'integer', Rule::exists('stores', 'id')->whereNull('deleted_at')],
            'servings_per_pack' => ['required', 'integer', 'min:1', 'max:100000'],
            'calories_per_pack' => ['required', 'integer', 'min:0', 'max:1000000'],
            'price_per_pack' => ['required', 'integer', 'min:0', 'max:100000000'],
            'priced_at' => ['required', 'date'],
        ];
    }
}
