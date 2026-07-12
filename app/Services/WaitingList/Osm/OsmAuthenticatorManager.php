<?php

namespace App\Services\WaitingList\Osm;

use App\Http\Integrations\Osm\OsmConnector;
use App\Settings\OsmSettings;
use DateTimeImmutable;
use RuntimeException;
use Saloon\Contracts\OAuthAuthenticator;
use Saloon\Http\Auth\AccessTokenAuthenticator;

class OsmAuthenticatorManager
{
    public function __construct(private readonly OsmSettings $osmSettings) {}

    public function resolveAuthenticator(OsmConnector $connector): OAuthAuthenticator
    {
        $authenticator = $this->storedAuthenticator();

        if ($authenticator instanceof OAuthAuthenticator && $authenticator->hasNotExpired()) {
            return $authenticator;
        }

        if ($authenticator instanceof OAuthAuthenticator && $authenticator->isRefreshable()) {
            return $this->refreshAuthenticator($connector, $authenticator);
        }

        if (filled($this->osmSettings->refresh_token)) {
            return $this->refreshAuthenticator($connector, (string) $this->osmSettings->refresh_token);
        }

        throw new RuntimeException('OSM is not connected. Use the OSM settings page to complete the OAuth connection flow.');
    }

    public function forceRefreshAuthenticator(OsmConnector $connector): OAuthAuthenticator
    {
        if (filled($this->osmSettings->refresh_token)) {
            return $this->refreshAuthenticator($connector, (string) $this->osmSettings->refresh_token);
        }

        throw new RuntimeException('OSM is not connected. Use the OSM settings page to complete the OAuth connection flow.');
    }

    public function exchangeAuthorizationCode(
        OsmConnector $connector,
        string $code,
        ?string $state = null,
        ?string $expectedState = null,
    ): OAuthAuthenticator {
        $authenticator = $connector->getAccessToken(
            code: $code,
            state: $state,
            expectedState: $expectedState,
        );

        $this->storeAuthenticator($authenticator);

        return $authenticator;
    }

    public function storeAuthenticator(OAuthAuthenticator $authenticator): void
    {
        $this->osmSettings->access_token = $authenticator->getAccessToken();
        $this->osmSettings->refresh_token = (string) ($authenticator->getRefreshToken() ?? $this->osmSettings->refresh_token);
        $this->osmSettings->access_token_expires_at = $authenticator->getExpiresAt()?->format(DATE_ATOM);
        $this->osmSettings->save();
    }

    public function disconnect(): void
    {
        $this->osmSettings->refresh_token = '';
        $this->osmSettings->access_token = '';
        $this->osmSettings->access_token_expires_at = null;
        $this->osmSettings->save();
    }

    private function refreshAuthenticator(OsmConnector $connector, OAuthAuthenticator|string $refreshToken): OAuthAuthenticator
    {
        $authenticator = $connector->refreshAccessToken($refreshToken);
        $this->storeAuthenticator($authenticator);

        return $authenticator;
    }

    private function storedAuthenticator(): ?OAuthAuthenticator
    {
        if (blank($this->osmSettings->access_token)) {
            return null;
        }

        $expiresAt = filled($this->osmSettings->access_token_expires_at)
            ? new DateTimeImmutable((string) $this->osmSettings->access_token_expires_at)
            : null;

        return new AccessTokenAuthenticator(
            accessToken: (string) $this->osmSettings->access_token,
            refreshToken: filled($this->osmSettings->refresh_token) ? (string) $this->osmSettings->refresh_token : null,
            expiresAt: $expiresAt,
        );
    }
}
