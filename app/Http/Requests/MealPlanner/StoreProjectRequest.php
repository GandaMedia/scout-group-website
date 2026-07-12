<?php

namespace App\Http\Requests\MealPlanner;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
            'people_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'event_date' => ['required', 'date'],
        ];
    }
}
