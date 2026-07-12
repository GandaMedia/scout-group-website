<?php

use App\Filament\Blocks\ContactDetailsBlock;
use App\Filament\Blocks\ContactFormBlock;
use App\Filament\Blocks\CtaBlock;
use App\Filament\Blocks\GoogleMapBlock;
use App\Filament\Blocks\HeroBlock;
use App\Filament\Blocks\ImageTextBlock;
use App\Filament\Blocks\RichTextBlock;
use App\Filament\Blocks\SectionLeadersBlock;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;
use Redberry\PageBuilderPlugin\Components\Forms\PageBuilder;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('uses the page builder field and reuses the page block classes', function () {
    Livewire::test(CreatePost::class)
        ->assertFormFieldExists('content', function (PageBuilder $field): bool {
            expect($field)->toBeInstanceOf(PageBuilder::class)
                ->and($field->getBlocks())->toBe(PostForm::blocks());

            return true;
        });

    expect(PostForm::blocks())->toBe(PageForm::blocks())
        ->toBe([
            HeroBlock::class,
            RichTextBlock::class,
            ImageTextBlock::class,
            SectionLeadersBlock::class,
            ContactDetailsBlock::class,
            ContactFormBlock::class,
            GoogleMapBlock::class,
            CtaBlock::class,
        ]);
});

it('persists post metadata, tags and page builder blocks when creating a post', function () {
    $tag = Tag::factory()->create(['name' => 'Camping']);

    Livewire::test(CreatePost::class)
        ->fillForm([
            'title' => 'District camp highlights',
            'slug' => 'district-camp-highlights',
            'author_name' => 'Akela',
            'is_password_protected' => false,
            'status' => 'PUBLISHED',
            'published_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'tags' => [$tag->id],
        ])
        ->set('data.content', [
            [
                'id' => (string) str()->uuid(),
                'block_type' => HeroBlock::class,
                'order' => 1,
                'data' => [
                    'eyebrow' => 'News',
                    'title' => 'District camp highlights',
                    'body' => 'A brilliant weekend away.',
                ],
            ],
            [
                'id' => (string) str()->uuid(),
                'block_type' => RichTextBlock::class,
                'order' => 2,
                'data' => [
                    'content' => '### Well done everyone',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $post = Post::query()
        ->where('slug', 'district-camp-highlights')
        ->with(['pageBuilderBlocks', 'tags'])
        ->sole();

    expect($post->author_name)->toBe('Akela')
        ->and($post->is_password_protected)->toBeFalse()
        ->and($post->published_at)->not->toBeNull()
        ->and($post->tags)->toHaveCount(1)
        ->and($post->tags->first()?->slug)->toBe('camping')
        ->and($post->pageBuilderBlocks)->toHaveCount(2)
        ->and($post->pageBuilderBlocks->first()?->block_type)->toBe(HeroBlock::class);
});
