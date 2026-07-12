<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            route('home'),
            route('news.index'),
            route('calendar'),
        ])->concat(
            Page::query()->published()->orderBy('slug')->get()->map->getShowUrl(),
        )->concat(
            Post::query()->published()->orderBy('slug')->get()->map->getShowUrl(),
        )->concat(
            CalendarEvent::query()->orderBy('starts_at')->get()
                ->map(fn (CalendarEvent $event): string => route('calendar.events.show', $event)),
        )->unique();

        $entries = $urls
            ->map(fn (string $url): string => '  <url><loc>'.htmlspecialchars($url, ENT_XML1).'</loc></url>')
            ->implode("\n");

        return response(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$entries}\n</urlset>\n",
            200,
            ['Content-Type' => 'application/xml'],
        );
    }
}
