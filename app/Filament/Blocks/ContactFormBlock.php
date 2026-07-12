<?php

namespace App\Filament\Blocks;

use App\Settings\GroupProfileSettings;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;

class ContactFormBlock extends BaseBlock
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
            TextInput::make('submit_label')
                ->required()
                ->maxLength(255),
        ];
    }

    public static function getView(): ?string
    {
        return 'admin.blocks.contact-form-block';
    }

    public static function getBlockTitleAttribute(): ?string
    {
        return 'title';
    }

    public static function formatForSingleView(array $data): array
    {
        $groupProfileSettings = app(GroupProfileSettings::class);
        $siteKey = config('services.turnstile.site_key');

        $data['turnstile_site_key'] = is_string($siteKey) ? $siteKey : null;
        $data['is_configured'] = filled($groupProfileSettings->contact_recipient_email)
            && filled($siteKey)
            && filled(config('services.turnstile.secret_key'));

        return $data;
    }
}
