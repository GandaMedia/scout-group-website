<?php

namespace App\Http\Integrations\Osm\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AcceptsJson;

class ListTermsRequest extends Request
{
    use AcceptsJson;

    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/api.php?action=getTerms';
    }
}
