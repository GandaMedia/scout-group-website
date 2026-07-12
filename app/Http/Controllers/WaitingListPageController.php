<?php

namespace App\Http\Controllers;

use App\Enums\Section;
use App\Settings\SectionSettings;
use Inertia\Inertia;
use Inertia\Response;

class WaitingListPageController extends Controller
{
    public function __invoke(string $section, SectionSettings $sectionSettings): Response
    {
        $resolvedSection = Section::fromSlug($section);
        $turnstileSiteKey = config('services.turnstile.site_key');
        $turnstileSecretKey = config('services.turnstile.secret_key');

        abort_unless($resolvedSection instanceof Section, 404);
        abort_unless($sectionSettings->isEnabled($resolvedSection), 404);

        return Inertia::render('WaitingList/Show', [
            'section' => [
                'slug' => $resolvedSection->slug(),
                'label' => $resolvedSection->value,
                'ageRange' => $resolvedSection->ageRangeLabel(),
                'title' => "Join the {$resolvedSection->value} waiting list",
                'intro' => "Use the short form below to register interest in {$resolvedSection->value}. We will store the details locally first and then sync them into our OSM waiting list.",
            ],
            'form' => [
                'turnstile_site_key' => is_string($turnstileSiteKey) ? $turnstileSiteKey : null,
                'is_configured' => filled($turnstileSiteKey)
                    && filled($turnstileSecretKey),
            ],
        ]);
    }
}
