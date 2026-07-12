<?php

use App\Models\Post;
use App\Models\Tag;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the news index with published posts in newest-first order', function () {
    $campTag = Tag::factory()->create(['name' => 'Camping']);

    $olderPost = Post::factory()->published()->unprotected()->create([
        'title' => 'Spring camp round-up',
        'published_at' => now()->subDays(5),
    ]);
    $olderPost->tags()->attach($campTag);

    $newerPost = Post::factory()->published()->unprotected()->create([
        'title' => 'Kayaking day highlights',
        'published_at' => now()->subDay(),
    ]);

    Post::factory()->create([
        'title' => 'Draft idea',
    ]);
    Post::factory()->scheduled()->create([
        'title' => 'Future jamboree update',
    ]);

    $this->get(route('news.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('News/Index')
            ->where('heading', 'News')
            ->where('posts', function ($posts) use ($olderPost, $newerPost): bool {
                expect($posts)->toHaveCount(2)
                    ->and($posts[0]['slug'])->toBe($newerPost->slug)
                    ->and($posts[1]['slug'])->toBe($olderPost->slug)
                    ->and($posts[1]['tags'][0]['slug'])->toBe('camping');

                return true;
            }));
});

it('hides author, tags and preview content for password protected posts in the index', function () {
    $protectedPost = Post::factory()->published()->create([
        'title' => 'Leaders planning night',
    ]);
    $protectedPost->tags()->attach(Tag::factory()->create(['name' => 'Planning']));

    $this->get(route('news.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('News/Index')
            ->where('posts.0.title', 'Leaders planning night')
            ->where('posts.0.author_name', null)
            ->where('posts.0.excerpt', null)
            ->where('posts.0.image', null)
            ->where('posts.0.tags', [])
            ->where('posts.0.is_password_protected', true));
});

it('renders a published post with serialized blocks and metadata', function () {
    $tag = Tag::factory()->create(['name' => 'Water']);
    $post = Post::factory()->published()->unprotected()->create([
        'title' => 'Paddleboard evening',
        'slug' => 'paddleboard-evening',
        'author_name' => 'Akela',
    ]);
    $post->tags()->attach($tag);
    $this->get(route('news.show', ['post' => $post->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('News/Show')
            ->where('post.title', 'Paddleboard evening')
            ->where('post.author_name', 'Akela')
            ->has('post.excerpt')
            ->has('post.image')
            ->where('post.tags.0.slug', 'water')
            ->where('post.blocks.0.type', 'HeroBlock')
            ->where('post.blocks.1.type', 'RichTextBlock')
            ->where('post.is_password_protected', false)
            ->where('post.is_authorized', true));
});

it('prompts for a password on protected posts until the cookie is present', function () {
    $post = Post::factory()->published()->create([
        'title' => 'County camp briefing',
        'author_name' => 'Akela',
    ]);

    $this->get(route('news.show', ['post' => $post->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('News/Show')
            ->where('post.title', 'County camp briefing')
            ->where('post.author_name', null)
            ->where('post.excerpt', null)
            ->where('post.image', null)
            ->where('post.tags', [])
            ->where('post.blocks', [])
            ->where('post.is_password_protected', true)
            ->where('post.is_authorized', false));
});

it('unlocks a protected post with the configured password and renews the cookie on later visits', function () {
    config()->set('news.password', 'Secret Scouts');

    $post = Post::factory()->published()->create([
        'slug' => 'county-camp-briefing',
    ]);

    $unlockResponse = $this->from(route('news.show', $post))
        ->post(route('news.unlock', $post), [
            'password' => 'Secret Scouts',
        ]);

    $unlockResponse
        ->assertRedirect(route('news.show', $post))
        ->assertCookieNotExpired(config('news.password_cookie'));

    $this->withCookie(
        config('news.password_cookie'),
        cookie(
            config('news.password_cookie'),
            hash('sha256', 'Secret Scouts|'.config('app.key')),
            config('news.password_cookie_minutes'),
        )->getValue()
    )->get(route('news.show', $post))
        ->assertSuccessful()
        ->assertCookieNotExpired(config('news.password_cookie'))
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->where('post.is_authorized', true)
            ->where('post.author_name', $post->author_name)
            ->has('post.blocks', 3));
});

it('rejects an incorrect password for a protected post', function () {
    config()->set('news.password', 'Secret Scouts');

    $post = Post::factory()->published()->create();

    $this->from(route('news.show', $post))
        ->post(route('news.unlock', $post), [
            'password' => 'Wrong Password',
        ])
        ->assertRedirect(route('news.show', $post))
        ->assertSessionHasErrors('password');
});

it('returns not found for non-public posts', function (Post $post) {
    $this->get(route('news.show', ['post' => $post->slug]))
        ->assertNotFound();
})->with([
    'draft post' => fn () => Post::factory()->create(['slug' => 'draft-post']),
    'scheduled post' => fn () => Post::factory()->scheduled()->create(['slug' => 'scheduled-post']),
]);

it('renders a tag archive with only matching published posts', function () {
    $waterTag = Tag::factory()->create(['name' => 'Water']);
    $campTag = Tag::factory()->create(['name' => 'Camping']);

    $matchingPost = Post::factory()->published()->create([
        'title' => 'Kayaking recap',
    ]);
    $matchingPost->tags()->attach($waterTag);

    $otherTagPost = Post::factory()->published()->create([
        'title' => 'Campfire songs',
    ]);
    $otherTagPost->tags()->attach($campTag);

    $draftMatchingPost = Post::factory()->create([
        'title' => 'Future water night',
    ]);
    $draftMatchingPost->tags()->attach($waterTag);

    $this->get(route('news.tag', $waterTag))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('News/Index')
            ->where('tag.slug', 'water')
            ->where('posts', function ($posts) use ($matchingPost): bool {
                expect($posts)->toHaveCount(1)
                    ->and($posts[0]['slug'])->toBe($matchingPost->slug);

                return true;
            }));
});
