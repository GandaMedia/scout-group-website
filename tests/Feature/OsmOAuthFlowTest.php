<?php

use App\Jobs\RefreshOsmDirectorySnapshot;
use App\Models\User;
use App\Settings\OsmSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\OAuth2\GetAccessTokenRequest;
use Saloon\Laravel\Facades\Saloon;

beforeEach(function () {
    config()->set('services.osm.client_id', 'client-id');
    config()->set('services.osm.client_secret', 'client-secret');
    config()->set('services.osm.redirect_uri', 'https://scout-group-website.test/admin/osm/callback');
    config()->set('services.osm.scopes', ['section:member:read', 'section:member:write']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->givePermissionTo('access admin');
    $this->actingAs($admin);
});

it('blocks pending users from OSM OAuth routes', function (string $route) {
    $this->actingAs(User::factory()->pendingApproval()->create());

    $this->get(route($route))->assertForbidden();
})->with([
    'connect' => 'osm.oauth.redirect',
    'callback' => 'osm.oauth.callback',
]);

it('blocks verified non-admin users from OSM OAuth routes', function (string $route) {
    $this->actingAs(User::factory()->create());

    $this->get(route($route))->assertForbidden();
})->with([
    'connect' => 'osm.oauth.redirect',
    'callback' => 'osm.oauth.callback',
]);

it('requires email verification for OSM OAuth routes', function (string $route) {
    $unverifiedAdmin = User::factory()->unverified()->create();
    $unverifiedAdmin->givePermissionTo('access admin');
    $this->actingAs($unverifiedAdmin);

    $this->get(route($route))->assertRedirect(route('verification.notice'));
})->with([
    'connect' => 'osm.oauth.redirect',
    'callback' => 'osm.oauth.callback',
]);

it('redirects admins into the OSM oauth flow', function () {
    $response = $this->get(route('osm.oauth.redirect'));

    $response->assertRedirect();

    expect($response->headers->get('Location'))
        ->toContain('https://www.onlinescoutmanager.co.uk/oauth/authorize')
        ->toContain('client_id=client-id')
        ->toContain('section%3Amember%3Aread')
        ->toContain('section%3Amember%3Awrite');

    expect(session('osm.oauth_state'))->not->toBeNull();
});

it('stores the oauth tokens from the callback response', function () {
    Queue::fake();

    Saloon::fake([
        GetAccessTokenRequest::class => MockResponse::make([
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'access_token' => 'fresh-access-token',
            'refresh_token' => 'fresh-refresh-token',
        ]),
    ]);

    session()->put('osm.oauth_state', 'expected-state');

    $response = $this->get(route('osm.oauth.callback', [
        'code' => 'auth-code',
        'state' => 'expected-state',
    ]));

    $response->assertRedirect(route('filament.admin.pages.osm-settings'));

    $settings = app(OsmSettings::class);

    expect($settings->access_token)->toBe('fresh-access-token')
        ->and($settings->refresh_token)->toBe('fresh-refresh-token')
        ->and($settings->access_token_expires_at)->not->toBeNull();

    Queue::assertPushed(RefreshOsmDirectorySnapshot::class);
});
