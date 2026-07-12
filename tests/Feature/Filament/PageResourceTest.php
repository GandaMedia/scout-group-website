<?php

use App\Filament\Blocks\ContactDetailsBlock;
use App\Filament\Blocks\ContactFormBlock;
use App\Filament\Blocks\CtaBlock;
use App\Filament\Blocks\GoogleMapBlock;
use App\Filament\Blocks\HeroBlock;
use App\Filament\Blocks\ImageTextBlock;
use App\Filament\Blocks\RichTextBlock;
use App\Filament\Blocks\SectionLeadersBlock;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;
use Redberry\PageBuilderPlugin\Components\Forms\PageBuilder;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('uses the page builder field and registers the scout block classes', function () {
    Livewire::test(CreatePage::class)
        ->assertFormFieldExists('content', function (PageBuilder $field): bool {
            expect($field)->toBeInstanceOf(PageBuilder::class)
                ->and($field->getBlocks())->toBe(PageForm::blocks());

            return true;
        });

    expect(PageForm::blocks())->toBe([
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

it('persists page builder blocks when creating a page', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Volunteer info',
            'slug' => 'volunteer-info',
            'status' => 'PUBLISHED',
        ])
        ->set('data.content', [
            [
                'id' => (string) str()->uuid(),
                'block_type' => HeroBlock::class,
                'order' => 1,
                'data' => [
                    'eyebrow' => 'Scouts',
                    'title' => 'Volunteer with us',
                    'body' => 'Help young people gain skills for life.',
                ],
            ],
            [
                'id' => (string) str()->uuid(),
                'block_type' => CtaBlock::class,
                'order' => 2,
                'data' => [
                    'title' => 'Start the conversation',
                    'body' => 'We are always happy to chat.',
                    'button_label' => 'Contact us',
                    'button_url' => '/contact',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $page = Page::query()->where('slug', 'volunteer-info')->with('pageBuilderBlocks')->sole();

    expect($page->pageBuilderBlocks)->toHaveCount(2)
        ->and($page->pageBuilderBlocks->first()?->block_type)->toBe(HeroBlock::class)
        ->and($page->pageBuilderBlocks->last()?->block_type)->toBe(CtaBlock::class);
});

it('hides system-owned section pages from the generic pages list', function () {
    $contactPage = Page::factory()->published()->create([
        'title' => 'Contact',
        'slug' => 'contact',
    ]);
    $sectionPage = Page::factory()->published()->create([
        'title' => 'Explorers',
        'slug' => 'explorers',
    ]);

    Livewire::test(ListPages::class)
        ->assertCanSeeTableRecords([$contactPage])
        ->assertCanNotSeeTableRecords([$sectionPage]);
});
