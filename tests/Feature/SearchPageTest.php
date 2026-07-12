<?php

use App\Models\Page;
use App\Models\Post;
use Inertia\Testing\AssertableInertia as Assert;

it('searches published page and news titles', function () {
    Page::factory()->published()->create(['title' => 'Preparing for summer camp']);
    Post::factory()->published()->unprotected()->create(['title' => 'Summer camp report']);
    Page::factory()->create(['title' => 'Summer draft notes']);
    Post::factory()->scheduled()->create(['title' => 'Summer announcement']);

    $this->get(route('search', ['q' => 'summer']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Search/Index')
            ->where('query', 'summer')
            ->has('results', 2)
            ->where('results.0.type', 'Page')
            ->where('results.0.title', 'Preparing for summer camp')
            ->where('results.1.type', 'News')
            ->where('results.1.title', 'Summer camp report'));
});

it('does not query for fewer than two characters', function () {
    Page::factory()->published()->create(['title' => 'A page']);

    $this->get(route('search', ['q' => 'a']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('query', 'a')
            ->has('results', 0));
});

it('validates the maximum search length', function () {
    $this->get(route('search', ['q' => str_repeat('a', 101)]))
        ->assertSessionHasErrors('q');
});

it('publishes crawl rules and a sitemap containing only published content', function () {
    $publishedPage = Page::factory()->published()->create(['title' => 'About the group']);
    $draftPage = Page::factory()->create(['title' => 'Private draft']);

    $this->get(route('robots'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /admin', escape: false)
        ->assertSee(route('sitemap'), escape: false);

    $this->get(route('sitemap'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('page.show', $publishedPage), escape: false)
        ->assertDontSee(route('page.show', $draftPage), escape: false);
});
