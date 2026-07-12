<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = trim($validated['q'] ?? '');
        $pages = collect();
        $posts = collect();

        if (mb_strlen($query) >= 2) {
            $pages = Page::query()
                ->published()
                ->where('title', 'like', "%{$query}%")
                ->orderBy('title')
                ->limit(20)
                ->get(['title', 'slug'])
                ->map(fn (Page $page): array => [
                    'type' => 'Page',
                    'title' => $page->title,
                    'href' => route('page.show', $page),
                ]);

            $posts = Post::query()
                ->published()
                ->where('title', 'like', "%{$query}%")
                ->orderByDesc('published_at')
                ->limit(20)
                ->get(['title', 'slug', 'published_at'])
                ->map(fn (Post $post): array => [
                    'type' => 'News',
                    'title' => $post->title,
                    'href' => route('news.show', $post),
                    'publishedAt' => $post->published_at?->toIso8601String(),
                ]);
        }

        return Inertia::render('Search/Index', [
            'query' => $query,
            'results' => $pages->concat($posts)->values()->all(),
        ]);
    }
}
