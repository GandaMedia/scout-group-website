<?php

namespace App\Filament\Blocks;

use App\Settings\GroupProfileSettings;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Support\Str;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;

class RichTextBlock extends BaseBlock
{
    public static function getBlockSchema(): array
    {
        return [
            MarkdownEditor::make('content')
                ->required()
                ->columnSpanFull(),
        ];
    }

    public static function getView(): ?string
    {
        return 'admin.blocks.rich-text-block';
    }

    public static function getBlockTitleAttribute(): ?string
    {
        return 'content';
    }

    public static function formatForSingleView(array $data): array
    {
        $content = str_replace(
            '{{ group_name }}',
            app(GroupProfileSettings::class)->group_name,
            (string) ($data['content'] ?? ''),
        );

        $data['content'] = Str::markdown($content, [
            'allow_unsafe_links' => false,
            'html_input' => 'strip',
        ]);

        return $data;
    }
}
