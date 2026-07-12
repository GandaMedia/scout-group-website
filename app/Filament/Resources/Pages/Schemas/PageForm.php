<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageStatus;
use App\Filament\Blocks\ContactDetailsBlock;
use App\Filament\Blocks\ContactFormBlock;
use App\Filament\Blocks\CtaBlock;
use App\Filament\Blocks\GoogleMapBlock;
use App\Filament\Blocks\HeroBlock;
use App\Filament\Blocks\ImageTextBlock;
use App\Filament\Blocks\RichTextBlock;
use App\Filament\Blocks\SectionLeadersBlock;
use App\Models\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Redberry\PageBuilderPlugin\Components\Forms\PageBuilder;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                        if (filled($get('slug'))) {
                            return;
                        }

                        $set('slug', Str::slug((string) $state));
                    }),

                TextInput::make('slug')
                    ->required(fn (string $context) => $context === 'edit')
                    ->maxLength(255)
                    ->unique(Page::class, 'slug', fn ($record) => $record)
                    ->disabled(fn (?string $operation, ?Page $record) => $operation === 'edit' && $record?->status === PageStatus::PUBLISHED),

                PageBuilder::make('content')
                    ->blocks(self::blocks())
                    ->selectBlockAction(fn ($action) => $action->selectField(fn (Select $field): Select => $field->native()))
                    ->reorderable()
                    ->required()
                    ->columnSpanFull(),

                Select::make('status')
                    ->default(PageStatus::DRAFT)
                    ->options(PageStatus::class),
            ]);
    }

    /**
     * @return array<class-string>
     */
    public static function blocks(): array
    {
        return [
            HeroBlock::class,
            RichTextBlock::class,
            ImageTextBlock::class,
            SectionLeadersBlock::class,
            ContactDetailsBlock::class,
            ContactFormBlock::class,
            GoogleMapBlock::class,
            CtaBlock::class,
        ];
    }
}
