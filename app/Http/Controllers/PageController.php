<?php

namespace App\Http\Controllers;

use App\Enums\Section;
use App\Models\Page;
use App\Services\PageBlockSerializer;
use App\Settings\SectionSettings;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(string $page, PageBlockSerializer $pageBlockSerializer, SectionSettings $sectionSettings): Response
    {
        $section = Section::fromSlug($page);

        abort_if($section instanceof Section && ! $sectionSettings->isEnabled($section), 404);

        $resolvedPage = Page::query()
            ->published()
            ->with('pageBuilderBlocks')
            ->where('slug', $page)
            ->firstOrFail();

        return Inertia::render('Page/Show', [
            'page' => [
                'title' => $resolvedPage->title,
                'slug' => $resolvedPage->slug,
                'blocks' => $pageBlockSerializer->serialize($resolvedPage),
            ],
        ]);
    }
}
