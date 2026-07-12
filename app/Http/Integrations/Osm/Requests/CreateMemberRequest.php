<?php

namespace App\Http\Integrations\Osm\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;
use Saloon\Traits\Plugins\AcceptsJson;

class CreateMemberRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasFormBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, string>  $payload
     */
    public function __construct(private readonly array $payload) {}

    public function resolveEndpoint(): string
    {
        return '/ext/members/contact/actions/?action=newMember';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return $this->payload;
    }
}
