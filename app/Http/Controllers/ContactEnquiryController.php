<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactEnquiryRequest;
use App\Mail\ContactEnquirySubmitted;
use App\Models\ContactEnquiry;
use App\Services\TurnstileVerifier;
use App\Settings\ContactSettings;
use App\Settings\GroupProfileSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ContactEnquiryController extends Controller
{
    public function __invoke(
        StoreContactEnquiryRequest $request,
        ContactSettings $contactSettings,
        GroupProfileSettings $groupProfileSettings,
        TurnstileVerifier $turnstileVerifier,
    ): RedirectResponse {
        $turnstileSiteKey = config('services.turnstile.site_key');
        $turnstileSecretKey = config('services.turnstile.secret_key');

        if (
            blank($groupProfileSettings->contact_recipient_email)
            || blank($turnstileSiteKey)
            || blank($turnstileSecretKey)
        ) {
            throw ValidationException::withMessages([
                'form' => 'The contact form is not configured right now. Please try again later.',
            ]);
        }

        $validated = $request->validated();

        if (! $turnstileVerifier->verify(
            token: $validated['turnstile_token'],
            secret: (string) $turnstileSecretKey,
            ip: $request->ip(),
        )) {
            throw ValidationException::withMessages([
                'turnstile' => 'We could not verify the security check. Please try again.',
            ]);
        }

        $contactEnquiry = ContactEnquiry::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'message' => $validated['message'],
            'submitted_at' => now(),
        ]);

        Mail::to($groupProfileSettings->contact_recipient_email, $groupProfileSettings->contact_recipient_name)
            ->send(new ContactEnquirySubmitted($contactEnquiry));

        return back()->with('contactEnquirySubmitted', $contactSettings->success_message);
    }
}
