<?php

namespace App\Http\Controllers\Osm;

use App\Http\Controllers\Controller;
use App\Http\Integrations\Osm\OsmConnector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class OsmOAuthRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (blank(config('services.osm.client_id')) || blank(config('services.osm.client_secret')) || blank(config('services.osm.redirect_uri'))) {
            throw new RuntimeException('OSM OAuth is not configured in the environment.');
        }

        $connector = new OsmConnector;
        $authorizationUrl = $connector->getAuthorizationUrl(scopes: config('services.osm.scopes', []));

        $request->session()->put('osm.oauth_state', $connector->getState());

        return redirect()->away($authorizationUrl);
    }
}
