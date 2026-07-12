<?php

namespace App\Http\Integrations\Osm;

use Illuminate\Support\Facades\Cache;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Helpers\RetryAfterHelper;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\OAuth2\AuthorizationCodeGrant;
use Saloon\Traits\Plugins\AcceptsJson;

class OsmConnector extends Connector
{
    use AcceptsJson;
    use AuthorizationCodeGrant;
    use HasRateLimits;

    public function resolveBaseUrl(): string
    {
        return 'https://www.onlinescoutmanager.co.uk';
    }

    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make()
            ->setClientId((string) config('services.osm.client_id'))
            ->setClientSecret((string) config('services.osm.client_secret'))
            ->setRedirectUri((string) config('services.osm.redirect_uri'))
            ->setAuthorizeEndpoint('/oauth/authorize')
            ->setTokenEndpoint('/oauth/token')
            ->setUserEndpoint('/oauth/resource');
    }

    protected function resolveLimits(): array
    {
        return [
            Limit::allow(10, threshold: 0.9)
                ->everyFiveMinutes()
                ->name('waiting-list-sync-five-minute-safety'),
            Limit::allow(100, threshold: 0.9)
                ->everyHour()
                ->name('waiting-list-sync-hourly-safety'),
        ];
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        return new LaravelCacheStore(Cache::store());
    }

    protected function getLimiterPrefix(): ?string
    {
        return 'osm:'.sha1((string) config('services.osm.client_id'));
    }

    protected function handleTooManyAttempts(Response $response, Limit $limit): void
    {
        if ($response->status() !== 429) {
            return;
        }

        $retryAfter = RetryAfterHelper::parse($response->header('Retry-After'));
        $rateLimitReset = $response->header('X-RateLimit-Reset');

        $limit->exceeded(
            releaseInSeconds: is_numeric($rateLimitReset)
                ? (int) $rateLimitReset
                : $retryAfter,
        );
    }
}
