<?php

namespace App\Filament\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;

class ContactDetailsBlock extends BaseBlock
{
    public static function getBlockSchema(): array
    {
        return [
            TextInput::make('eyebrow')
                ->required()
                ->maxLength(255),
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            Textarea::make('intro')
                ->rows(4)
                ->required()
                ->columnSpanFull(),
            Repeater::make('cards')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('body')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->defaultItems(3)
                ->minItems(1)
                ->maxItems(4)
                ->required()
                ->columnSpanFull(),
            TextInput::make('primary_label')
                ->label('Primary button label')
                ->maxLength(255),
            TextInput::make('primary_url')
                ->label('Primary button URL')
                ->maxLength(255),
            TextInput::make('secondary_label')
                ->label('Secondary button label')
                ->maxLength(255),
            TextInput::make('secondary_url')
                ->label('Secondary button URL')
                ->maxLength(255),
        ];
    }

    public static function getView(): ?string
    {
        return 'admin.blocks.contact-details-block';
    }

    public static function getBlockTitleAttribute(): ?string
    {
        return 'title';
    }

    public static function formatForSingleView(array $data): array
    {
        $data['cards'] = collect($data['cards'] ?? [])
            ->filter(fn (mixed $card): bool => is_array($card))
            ->values()
            ->all();

        return $data;
    }
}
