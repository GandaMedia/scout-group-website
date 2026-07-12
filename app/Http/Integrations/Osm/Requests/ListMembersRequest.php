<?php

namespace App\Http\Integrations\Osm\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AcceptsJson;

class ListMembersRequest extends Request
{
    use AcceptsJson;

    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $sectionId,
        private readonly string $termId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/ext/members/contact/';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'action' => 'getListOfMembers',
            'sectionid' => $this->sectionId,
            'termid' => $this->termId,
            'sort' => 'firstname',
        ];
    }
}
