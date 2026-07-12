<?php

namespace App\Http\Controllers;

use App\Filament\Blocks\HeroBlock;
use App\Filament\Blocks\ImageTextBlock;
use App\Filament\Blocks\RichTextBlock;
use App\Http\Requests\UnlockPostRequest;
use App\Models\Post;
use App\Models\Tag;
use App\Services\PageBlockSerializer;
use App\Services\PostPasswordGate;
use App\Settings\GroupProfileSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;
use Redberry\PageBuilderPlugin\Models\PageBuilderBlock;

class PostController extends Controller
{
    public function index(GroupProfileSettings $groupProfileSettings): Response
    {
        return Inertia::render('News/Index', [
            'heading' => 'News',
            'description' => 'Stories, updates and adventures from around '.$groupProfileSettings->group_name.'.',
            'tag' => null,
            'posts' => $this->publishedPostsQuery()
                ->get()
                ->map(fn (Post $post): array => $this->toPostSummary($post, $groupProfileSettings))
                ->all(),
        ]);
    }

    public function show(
        Request $request,
        string $post,
        PageBlockSerializer $pageBlockSerializer,
        PostPasswordGate $postPasswordGate,
    ): Response {
        $resolvedPost = $this->resolvePublishedPost($post);

        if ($postPasswordGate->protects($resolvedPost) && ! $postPasswordGate->isAuthorized($request)) {
            return Inertia::render('News/Show', [
                'post' => [
                    'title' => $resolvedPost->title,
                    'slug' => $resolvedPost->slug,
                    'author_name' => null,
                    'published_at' => $resolvedPost->published_at?->toIso8601String(),
                    'tags' => [],
                    'blocks' => [],
                    'is_password_protected' => true,
                    'is_authorized' => false,
                ],
            ]);
        }

        if ($postPasswordGate->protects($resolvedPost)) {
            Cookie::queue($postPasswordGate->authorizationCookie());
        }

        return Inertia::render('News/Show', [
            'post' => [
                'title' => $resolvedPost->title,
                'slug' => $resolvedPost->slug,
                'author_name' => $resolvedPost->author_name,
                'published_at' => $resolvedPost->published_at?->toIso8601String(),
                'tags' => $this->serializeTags($resolvedPost),
                'blocks' => $pageBlockSerializer->serialize($resolvedPost),
                'is_password_protected' => $resolvedPost->is_password_protected,
                'is_authorized' => true,
            ],
        ]);
    }

    public function unlock(
        UnlockPostRequest $request,
        string $post,
        PostPasswordGate $postPasswordGate,
    ): RedirectResponse {
        $resolvedPost = $this->resolvePublishedPost($post);

        if (! $postPasswordGate->protects($resolvedPost)) {
            return to_route('news.show', $resolvedPost);
        }

        if (! $postPasswordGate->passwordMatches((string) $request->validated('password'))) {
            return back()->withErrors([
                'password' => 'That password was not recognised.',
            ]);
        }

        return to_route('news.show', $resolvedPost)
            ->cookie($postPasswordGate->authorizationCookie());
    }

    public function tag(Tag $tag, GroupProfileSettings $groupProfileSettings): Response
    {
        return Inertia::render('News/Index', [
            'heading' => "Tagged: {$tag->name}",
            'description' => "Every {$groupProfileSettings->group_short_name} update tagged {$tag->name}.",
            'tag' => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
            'posts' => $this->publishedPostsQuery()
                ->whereHas('tags', fn (Builder $query) => $query->whereKey($tag->getKey()))
                ->get()
                ->map(fn (Post $post): array => $this->toPostSummary($post, $groupProfileSettings))
                ->all(),
        ]);
    }

    private function publishedPostsQuery(): Builder
    {
        return Post::query()
            ->published()
            ->with(['tags', 'pageBuilderBlocks'])
            ->orderByDesc('published_at');
    }

    private function resolvePublishedPost(string $slug): Post
    {
        return $this->publishedPostsQuery()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * @return array{title: string, slug: string, author_name: ?string, published_at: ?string, excerpt: ?string, image: ?string, tags: array<int, array{name: string, slug: string}>, is_password_protected: bool}
     */
    private function toPostSummary(Post $post, ?GroupProfileSettings $groupProfileSettings = null): array
    {
        if ($post->is_password_protected) {
            return [
                'title' => $post->title,
                'slug' => $post->slug,
                'author_name' => null,
                'published_at' => $post->published_at?->toIso8601String(),
                'excerpt' => null,
                'image' => null,
                'tags' => [],
                'is_password_protected' => true,
            ];
        }

        [$excerpt, $image] = $this->resolvePreview($post);

        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'author_name' => $post->author_name,
            'published_at' => $post->published_at?->toIso8601String(),
            'excerpt' => $excerpt ?? 'Read the latest update from '.($groupProfileSettings ?? app(GroupProfileSettings::class))->group_name.'.',
            'image' => $image,
            'tags' => $this->serializeTags($post),
            'is_password_protected' => false,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolvePreview(Post $post): array
    {
        $excerpt = null;
        $image = null;

        foreach ($post->pageBuilderBlocks->sortBy('order') as $block) {
            $blockType = $block->block_type;
            $data = $this->formatBlockData($block);

            if ($image === null && filled($data['image'] ?? null)) {
                $image = $data['image'];
            }

            if ($excerpt !== null) {
                continue;
            }

            $excerpt = match ($blockType) {
                HeroBlock::class => $this->plainText($data['body'] ?? null),
                ImageTextBlock::class, RichTextBlock::class => $this->markdownToPlainText($data['content'] ?? null),
                default => null,
            };
        }

        return [
            filled($excerpt) ? Str::limit($excerpt, 180) : null,
            $image,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBlockData(PageBuilderBlock $block): array
    {
        $blockType = $block->block_type;
        $data = $block->data ?? [];

        if (is_subclass_of($blockType, BaseBlock::class)) {
            $data = $blockType::formatForSingleView($data);
        }

        return $data;
    }

    private function markdownToPlainText(?string $markdown): ?string
    {
        if (blank($markdown)) {
            return null;
        }

        return $this->plainText(Str::markdown($markdown, [
            'allow_unsafe_links' => false,
            'html_input' => 'strip',
        ]));
    }

    private function plainText(?string $text): ?string
    {
        if (blank($text)) {
            return null;
        }

        return Str::of(strip_tags($text))
            ->squish()
            ->toString();
    }

    /**
     * @return array<int, array{name: string, slug: string}>
     */
    private function serializeTags(Post $post): array
    {
        return $post->tags
            ->map(fn (Tag $tag): array => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])
            ->values()
            ->all();
    }
}
