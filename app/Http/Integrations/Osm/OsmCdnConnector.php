<?php

namespace App\Http\Integrations\Osm;

use Saloon\Http\Connector;

class OsmCdnConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://oymcdn.co.uk';
    }
}
