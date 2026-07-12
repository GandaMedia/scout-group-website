<?php

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Tag;

it('creates a valid draft post with the factory defaults', function () {
    $post = Post::factory()->create();

    expect($post->title)->not->toBeNull()
        ->and($post->title)->toBeString()
        ->and($post->slug)->not->toBeNull()
        ->and($post->slug)->toBeString()
        ->and($post->author_name)->not->toBeNull()
        ->and($post->author_name)->toBeString()
        ->and($post->is_password_protected)->toBeTrue()
        ->and($post->status)->toBe(PostStatus::DRAFT)
        ->and($post->published_at)->toBeNull()
        ->and($post->pageBuilderBlocks)->toHaveCount(3)
        ->and($post->pageBuilderBlocks->first()?->data['title'])->toBe($post->title);
});

it('supports published and scheduled factory states', function () {
    $publishedPost = Post::factory()->published()->create();
    $scheduledPost = Post::factory()->scheduled()->create();

    expect($publishedPost->status)->toBe(PostStatus::PUBLISHED)
        ->and($publishedPost->published_at)->not->toBeNull()
        ->and($publishedPost->published_at?->isPast())->toBeTrue()
        ->and($scheduledPost->status)->toBe(PostStatus::PUBLISHED)
        ->and($scheduledPost->published_at)->not->toBeNull()
        ->and($scheduledPost->published_at?->isFuture())->toBeTrue();
});

it('supports unprotected factory state', function () {
    $post = Post::factory()->unprotected()->create();

    expect($post->is_password_protected)->toBeFalse();
});

it('can attach tags created via the factory', function () {
    $post = Post::factory()->create();
    $tag = Tag::factory()->create();

    $post->tags()->attach($tag);

    expect($post->fresh()->tags)->toHaveCount(1)
        ->and($post->fresh()->tags->first()?->is($tag))->toBeTrue();
});
