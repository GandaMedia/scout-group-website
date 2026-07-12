<?php

use App\Settings\GroupProfileSettings;
use App\Settings\HomePageSettings;
use App\Settings\SectionSettings;
use Inertia\Testing\AssertableInertia as Assert;

it('renders home page section cards from settings', function () {
    $groupProfileSettings = app(GroupProfileSettings::class);
    $groupProfileSettings->group_name = 'Example Scout Group';
    $groupProfileSettings->group_short_name = 'Example Scouts';
    $groupProfileSettings->logo_short_label = 'Example';
    $groupProfileSettings->logo_stacked_line_1 = 'Example';
    $groupProfileSettings->logo_stacked_line_2 = 'Scout Group';
    $groupProfileSettings->save();

    $settings = app(HomePageSettings::class);
    $settings->section_cards = [
        [
            'section' => 'Explorers',
            'age_range' => '14 - 18 years old',
            'time_slot' => 'Mondays: 7 - 9pm',
            'description' => 'Young people planning their next challenge.',
            'page_slug' => 'explorers',
        ],
    ];
    $settings->save();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('Home')
            ->where('sectionCards.0.section', 'Explorers')
            ->where('sectionCards.0.age_range', '14 - 18 years old')
            ->where('sectionCards.0.time_slot', 'Mondays: 7 - 9pm')
            ->where('sectionCards.0.description', 'Young people planning their next challenge.')
            ->where('sectionCards.0.page_slug', 'explorers')
            ->where('groupProfile.name', 'Example Scout Group')
            ->where('groupProfile.shortName', 'Example Scouts')
            ->where('groupProfile.logo.shortLabel', 'Example')
            ->where('groupProfile.logo.stackedLine1', 'Example')
            ->where('groupProfile.logo.stackedLine2', 'Scout Group'));
});

it('omits disabled section cards from the home page', function () {
    $settings = app(HomePageSettings::class);
    $settings->section_cards = [
        [
            'section' => 'Explorers',
            'age_range' => '14 - 18 years old',
            'time_slot' => 'Mondays: 7 - 9pm',
            'description' => 'Young people planning their next challenge.',
            'page_slug' => 'explorers',
        ],
        [
            'section' => 'Network',
            'age_range' => '18 - 25 years old',
            'time_slot' => 'Flexible',
            'description' => 'Young adults keeping adventure alive.',
            'page_slug' => 'network',
        ],
    ];
    $settings->save();

    $sectionSettings = app(SectionSettings::class);
    $sectionSettings->enabled_section_slugs = ['network'];
    $sectionSettings->save();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->where('sectionCards.0.section', 'Network')
            ->missing('sectionCards.1'));
});
