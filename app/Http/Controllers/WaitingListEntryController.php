<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWaitingListEntryRequest;
use App\Services\TurnstileVerifier;
use App\Services\WaitingList\WaitingListSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class WaitingListEntryController extends Controller
{
    public function __invoke(
        StoreWaitingListEntryRequest $request,
        TurnstileVerifier $turnstileVerifier,
        WaitingListSubmissionService $waitingListSubmissionService,
    ): RedirectResponse {
        $turnstileSiteKey = config('services.turnstile.site_key');
        $turnstileSecretKey = config('services.turnstile.secret_key');

        if (
            blank($turnstileSiteKey)
            || blank($turnstileSecretKey)
        ) {
            throw ValidationException::withMessages([
                'form' => 'The waiting list form is not configured right now. Please try again later.',
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

        $waitingListSubmissionService->create($validated);

        return back()->with(
            'waitingListSubmitted',
            'Thanks, your child has been added to our waiting list. We will be in touch soon!',
        );
    }
}
