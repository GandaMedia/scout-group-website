<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    public function verify(string $token, string $secret, ?string $ip = null): bool
    {
        if (blank($token) || blank($secret)) {
            return false;
        }

        $response = rescue(
            fn () => Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ], fn (mixed $value): bool => filled($value)))
                ->throw()
                ->json(),
            rescue: ['success' => false],
            report: false,
        );

        return (bool) data_get($response, 'success', false);
    }
}
