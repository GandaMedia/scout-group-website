<?php

namespace App\Http\Requests;

use App\Settings\SectionSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWaitingListEntryRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'section_slug' => ['required', Rule::in(array_keys(app(SectionSettings::class)->enabledOptionsBySlug()))],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_email' => ['required', 'email', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'turnstile_token' => ['required', 'string'],
        ];
    }
}
