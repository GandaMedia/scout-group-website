<?php

namespace App\Services\WaitingList\Osm;

use App\Http\Integrations\Osm\OsmConnector;
use App\Http\Integrations\Osm\Requests\ListSectionsRequest;
use App\Http\Integrations\Osm\Requests\ListTermsRequest;
use RuntimeException;
use Saloon\Contracts\OAuthAuthenticator;
use Saloon\Http\Response;

class OsmDirectoryService
{
    public function __construct(private readonly OsmAuthenticatorManager $authenticatorManager) {}

    /**
     * @return array{
     *     account_name: string|null,
     *     account_email: string|null,
     *     sections: array<string, string>,
     *     terms_by_section: array<string, array<string, string>>,
     * }
     */
    public function fetchDirectory(): array
    {
        $connector = new OsmConnector;
        $authenticator = $this->authenticatorManager->resolveAuthenticator($connector);

        $resourceResponse = $this->sendWithRefresh(
            connector: $connector,
            authenticator: $authenticator,
            send: fn (OsmConnector $connector, OAuthAuthenticator $authenticator): Response => $connector->getUser($authenticator),
        );

        $resourceOwner = $resourceResponse->json();

        if (! is_array($resourceOwner)) {
            throw new RuntimeException('OSM resource-owner response was not a valid JSON object.');
        }

        $sectionsResponse = $this->sendWithRefresh(
            connector: $connector,
            authenticator: $authenticator,
            send: fn (OsmConnector $connector, OAuthAuthenticator $authenticator): Response => $connector
                ->authenticate($authenticator)
                ->send(new ListSectionsRequest),
        );

        $termsResponse = $this->sendWithRefresh(
            connector: $connector,
            authenticator: $authenticator,
            send: fn (OsmConnector $connector, OAuthAuthenticator $authenticator): Response => $connector
                ->authenticate($authenticator)
                ->send(new ListTermsRequest),
        );

        return [
            'account_name' => $this->resolveAccountName($resourceOwner),
            'account_email' => $this->resolveAccountEmail($resourceOwner),
            'sections' => $this->resolveSectionOptions(
                resourceOwner: $resourceOwner,
                sectionsPayload: $sectionsResponse->json(),
            ),
            'terms_by_section' => $this->resolveTermsBySection($termsResponse->json()),
        ];
    }

    /**
     * @param  callable(OsmConnector, OAuthAuthenticator): Response  $send
     */
    private function sendWithRefresh(OsmConnector $connector, OAuthAuthenticator &$authenticator, callable $send): Response
    {
        $response = $send($connector, $authenticator);

        if ($response->status() === 401) {
            $authenticator = $this->authenticatorManager->forceRefreshAuthenticator($connector);
            $response = $send($connector, $authenticator);
        }

        $this->guardResponse($response);

        if ($response->failed()) {
            throw new RuntimeException('OSM directory request failed with HTTP '.$response->status().'.');
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $resourceOwner
     */
    private function resolveAccountName(array $resourceOwner): ?string
    {
        $name = $resourceOwner['name']
            ?? $resourceOwner['full_name']
            ?? trim(implode(' ', array_filter([
                $resourceOwner['firstname'] ?? null,
                $resourceOwner['lastname'] ?? null,
            ])));

        return is_string($name) && filled($name) ? $name : null;
    }

    /**
     * @param  array<string, mixed>  $resourceOwner
     */
    private function resolveAccountEmail(array $resourceOwner): ?string
    {
        $email = $resourceOwner['email'] ?? null;

        return is_string($email) && filled($email) ? $email : null;
    }

    /**
     * @param  array<string, mixed>  $resourceOwner
     * @return array<string, string>
     */
    private function resolveSectionOptions(array $resourceOwner, mixed $sectionsPayload): array
    {
        $options = $this->parseSectionOptions($resourceOwner['sections'] ?? null);

        if ($options !== []) {
            return $options;
        }

        if (is_array($sectionsPayload)) {
            return $this->parseSectionOptions($sectionsPayload['items'] ?? $sectionsPayload);
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function parseSectionOptions(mixed $sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        $options = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $sectionId = $section['sectionid'] ?? $section['section_id'] ?? $section['id'] ?? null;
            $label = $section['name'] ?? $section['section'] ?? $section['section_name'] ?? null;

            if (! is_scalar($sectionId) || ! is_scalar($label)) {
                continue;
            }

            $options[(string) $sectionId] = (string) $label;
        }

        asort($options);

        return $options;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function resolveTermsBySection(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $termsBySection = [];

        foreach ($payload as $sectionTerms) {
            if (! is_array($sectionTerms)) {
                continue;
            }

            foreach ($sectionTerms as $term) {
                if (! is_array($term)) {
                    continue;
                }

                $sectionId = $term['sectionid'] ?? null;
                $termId = $term['termid'] ?? $term['term_id'] ?? null;
                $termName = $term['name'] ?? null;

                if (! is_scalar($sectionId) || ! is_scalar($termId) || ! is_scalar($termName)) {
                    continue;
                }

                $label = (string) $termName;

                if (filled($term['startdate'] ?? null) && filled($term['enddate'] ?? null)) {
                    $label .= ' ('.(string) $term['startdate'].' to '.(string) $term['enddate'].')';
                }

                $termsBySection[(string) $sectionId][(string) $termId] = $label;
            }
        }

        foreach ($termsBySection as &$terms) {
            asort($terms);
        }

        ksort($termsBySection);

        return $termsBySection;
    }

    private function guardResponse(Response $response): void
    {
        $blockedReason = $response->header('X-Blocked');

        if (is_string($blockedReason) && $blockedReason !== '') {
            throw new RuntimeException('OSM has blocked this application for the connected user: '.$blockedReason);
        }
    }
}
