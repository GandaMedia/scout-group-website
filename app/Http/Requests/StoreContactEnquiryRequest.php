<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'turnstile_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'turnstile_token.required' => 'Please complete the security check before sending your message.',
        ];
    }
}
