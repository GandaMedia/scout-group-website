<?php

namespace App\Filament\Blocks;

use App\Settings\GroupProfileSettings;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;

class GoogleMapBlock extends BaseBlock
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
                ->required()
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    public static function getView(): ?string
    {
        return 'admin.blocks.google-map-block';
    }

    public static function getBlockTitleAttribute(): ?string
    {
        return 'title';
    }

    public static function formatForSingleView(array $data): array
    {
        $groupProfileSettings = app(GroupProfileSettings::class);

        $data['map_embed_url'] = $groupProfileSettings->map_embed_url;
        $data['map_label'] = $groupProfileSettings->headquarters_label;
        $data['map_address'] = $groupProfileSettings->headquarters_address;
        $data['has_map'] = filled($groupProfileSettings->map_embed_url);

        return $data;
    }
}
