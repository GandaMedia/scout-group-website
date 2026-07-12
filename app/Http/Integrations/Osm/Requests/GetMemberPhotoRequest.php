<?php

namespace App\Http\Integrations\Osm\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetMemberPhotoRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $scoutId,
        private readonly string $photoGuid,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf(
            '/sites/onlinescoutmanager.co.uk/public/member_photos/%s/%s/%s/250x250_0.jpg',
            $this->photoBucket(),
            $this->scoutId,
            $this->photoGuid,
        );
    }

    private function photoBucket(): string
    {
        return (string) (((int) floor(((int) $this->scoutId) / 5000)) * 5000);
    }
}
