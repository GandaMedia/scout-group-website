<?php

use App\Settings\OsmSettings;

it('decodes double-encoded OSM directory snapshots from settings storage', function () {
    $settings = app(OsmSettings::class);
    $settings->directory_sections = json_encode(json_encode([
        '301' => 'Example Group Beavers',
    ], JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR);
    $settings->directory_terms_by_section = json_encode(json_encode([
        '301' => [
            '401' => 'Current Beavers',
        ],
    ], JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR);
    $settings->save();

    $freshSettings = app(OsmSettings::class);

    expect($freshSettings->directorySections())->toBe([
        '301' => 'Example Group Beavers',
    ])->and($freshSettings->directoryTermsBySection())->toBe([
        '301' => [
            '401' => 'Current Beavers',
        ],
    ]);
});
