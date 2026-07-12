<?php

namespace App\Filament\Blocks;

use App\Filament\Blocks\Concerns\ResolvesBlockImageUrl;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;

class ImageTextBlock extends BaseBlock
{
    use ResolvesBlockImageUrl;

    public static function getBlockSchema(): array
    {
        return [
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            MarkdownEditor::make('content')
                ->required()
                ->columnSpanFull(),
            FileUpload::make('image')
                ->image()
                ->disk('public')
                ->directory('page-blocks')
                ->visibility('public')
                ->required()
                ->columnSpanFull(),
            Select::make('image_position')
                ->options([
                    'left' => 'Left',
                    'right' => 'Right',
                ])
                ->default('left')
                ->native(false)
                ->required(),
            Select::make('image_width')
                ->options([
                    'one-third' => '1/3',
                    'one-half' => '1/2',
                    'two-thirds' => '2/3',
                ])
                ->default('one-half')
                ->native(false)
                ->required(),
        ];
    }

    public static function getView(): ?string
    {
        return 'admin.blocks.image-text-block';
    }

    public static function getBlockTitleAttribute(): ?string
    {
        return 'title';
    }

    public static function formatForSingleView(array $data): array
    {
        $data['content'] = Str::markdown((string) ($data['content'] ?? ''), [
            'allow_unsafe_links' => false,
            'html_input' => 'strip',
        ]);
        $data['image'] = static::resolveBlockImageUrl($data['image'] ?? null);
        $data['image_position'] = $data['image_position'] ?? 'left';
        $data['image_width'] = $data['image_width'] ?? 'one-half';

        return $data;
    }
}
