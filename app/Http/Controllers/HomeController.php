<?php

namespace App\Http\Controllers;

use App\Enums\Section;
use App\Settings\HomePageSettings;
use App\Settings\SectionSettings;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(HomePageSettings $homePageSettings, SectionSettings $sectionSettings): Response
    {
        return Inertia::render('Home', [
            'sectionCards' => collect($homePageSettings->section_cards)
                ->filter(function (array $card) use ($sectionSettings): bool {
                    $section = Section::fromSlug((string) ($card['page_slug'] ?? ''));

                    return ! $section instanceof Section || $sectionSettings->isEnabled($section);
                })
                ->values()
                ->all(),
        ]);
    }
}
