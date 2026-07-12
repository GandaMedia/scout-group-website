<?php

namespace App\Http\Controllers\Osm;

use App\Http\Controllers\Controller;
use App\Http\Integrations\Osm\OsmConnector;
use App\Jobs\RefreshOsmDirectorySnapshot;
use App\Services\WaitingList\Osm\OsmAuthenticatorManager;
use App\Settings\OsmSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class OsmOAuthCallbackController extends Controller
{
    public function __invoke(Request $request, OsmAuthenticatorManager $osmAuthenticatorManager): RedirectResponse
    {
        $pageUrl = route('filament.admin.pages.osm-settings');

        if (filled($request->string('error')->value())) {
            return redirect($pageUrl)->with('osm_oauth_error', 'OSM authorisation was declined: '.$request->string('error')->value());
        }

        $code = $request->string('code')->value();

        if ($code === '') {
            return redirect($pageUrl)->with('osm_oauth_error', 'OSM did not return an authorisation code.');
        }

        try {
            $osmAuthenticatorManager->exchangeAuthorizationCode(
                connector: new OsmConnector,
                code: $code,
                state: $request->string('state')->value() ?: null,
                expectedState: $request->session()->pull('osm.oauth_state'),
            );
            app(OsmSettings::class)->markDirectoryRefreshQueued();
            RefreshOsmDirectorySnapshot::dispatch();
        } catch (Throwable $throwable) {
            return redirect($pageUrl)->with('osm_oauth_error', 'OSM connection failed: '.$throwable->getMessage());
        }

        return redirect($pageUrl)->with('osm_oauth_status', 'OSM connected successfully. A directory refresh has been queued.');
    }
}
