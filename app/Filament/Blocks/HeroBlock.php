<?php

namespace App\Filament\Blocks;

use App\Filament\Blocks\Concerns\ResolvesBlockImageUrl;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;

class HeroBlock extends BaseBlock
{
    use ResolvesBlockImageUrl;

    public static function getBlockSchema(): array
    {
        return [
            TextInput::make('eyebrow')
                ->required()
                ->maxLength(255),
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            Textarea::make('body')
                ->rows(4)
                ->required()
                ->columnSpanFull(),
            FileUpload::make('image')
                ->image()
                ->disk('public')
                ->directory('page-blocks')
                ->visibility('public')
                ->columnSpanFull(),
            TextInput::make('primary_label')
                ->label('Button label')
                ->maxLength(255),
            TextInput::make('primary_url')
                ->label('Button URL')
                ->maxLength(255),
        ];
    }

    public static function getView(): ?string
    {
        return 'admin.blocks.hero-block';
    }

    public static function getBlockTitleAttribute(): ?string
    {
        return 'title';
    }

    public static function formatForSingleView(array $data): array
    {
        $data['image'] = static::resolveBlockImageUrl($data['image'] ?? null);

        return $data;
    }
}
