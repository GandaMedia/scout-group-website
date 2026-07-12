<?php

use App\Http\Integrations\Osm\OsmCdnConnector;
use App\Http\Integrations\Osm\OsmConnector;
use App\Http\Integrations\Osm\Requests\CreateMemberRequest;
use App\Http\Integrations\Osm\Requests\GetMemberPhotoRequest;
use App\Http\Integrations\Osm\Requests\ListMembersRequest;
use App\Http\Integrations\Osm\Requests\ListSectionsRequest;
use App\Http\Integrations\Osm\Requests\ListTermsRequest;

it('keeps the osm connector aligned with our saloon conventions', function () {
    expect(OsmConnector::class)
        ->toBeSaloonConnector()
        ->toUseAcceptsJsonTrait()
        ->toUseAuthorisationCodeGrantTrait()
        ->toHaveRateLimits();
});

it('keeps the osm cdn connector aligned with our saloon conventions', function () {
    expect(OsmCdnConnector::class)
        ->toBeSaloonConnector();
});

it('keeps the osm create-member request aligned with our saloon conventions', function () {
    expect(CreateMemberRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toHaveFormBody()
        ->toUseAcceptsJsonTrait();
});

it('keeps the osm directory requests aligned with our saloon conventions', function () {
    expect(ListSectionsRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toUseAcceptsJsonTrait();

    expect(ListTermsRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toUseAcceptsJsonTrait();
});

it('keeps the osm member-list request aligned with our saloon conventions', function () {
    expect(ListMembersRequest::class)
        ->toBeSaloonRequest()
        ->toSendGetRequest()
        ->toUseAcceptsJsonTrait();
});

it('keeps the osm member-photo request aligned with our saloon conventions', function () {
    expect(GetMemberPhotoRequest::class)
        ->toBeSaloonRequest()
        ->toSendGetRequest();
});
