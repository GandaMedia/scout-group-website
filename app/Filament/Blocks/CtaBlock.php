<?php

namespace App\Filament\Blocks;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;

class CtaBlock extends BaseBlock
{
    public static function getBlockSchema(): array
    {
        return [
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            Textarea::make('body')
                ->rows(4)
                ->required()
                ->columnSpanFull(),
            TextInput::make('button_label')
                ->required()
                ->maxLength(255),
            TextInput::make('button_url')
                ->required()
                ->maxLength(255),
        ];
    }

    public static function getView(): ?string
    {
        return 'admin.blocks.cta-block';
    }

    public static function getBlockTitleAttribute(): ?string
    {
        return 'title';
    }
}
