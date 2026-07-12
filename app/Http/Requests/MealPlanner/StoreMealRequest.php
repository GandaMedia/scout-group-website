<?php

namespace App\Http\Requests\MealPlanner;

use App\Enums\MealType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMealRequest extends FormRequest
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
            'meal_type' => ['required', Rule::enum(MealType::class)],
            'day_number' => ['nullable', 'integer', 'min:1', 'max:366'],
        ];
    }
}
