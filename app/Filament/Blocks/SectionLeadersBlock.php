<?php

namespace App\Filament\Blocks;

use App\Enums\Section;
use App\Models\LeaderSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;

class SectionLeadersBlock extends BaseBlock
{
    public static function getBlockSchema(): array
    {
        return [
            Select::make('section')
                ->options(Section::class)
                ->native(false)
                ->required(),
            TextInput::make('eyebrow')
                ->default('Meet the team')
                ->required()
                ->maxLength(255),
            TextInput::make('title')
                ->default('Meet our leaders')
                ->required()
                ->maxLength(255),
            Textarea::make('intro')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    public static function getView(): ?string
    {
        return 'admin.blocks.section-leaders-block';
    }

    public static function getBlockTitleAttribute(): ?string
    {
        return 'title';
    }

    public static function formatForSingleView(array $data): array
    {
        $section = Section::tryFrom((string) ($data['section'] ?? ''));

        $data['eyebrow'] = $data['eyebrow'] ?? 'Meet the team';
        $data['title'] = $data['title'] ?? 'Meet our leaders';
        $data['intro'] = $data['intro'] ?? '';
        $data['leaders'] = [];

        if (! $section) {
            return $data;
        }

        $data['leaders'] = LeaderSection::query()
            ->with('leader.media')
            ->where('section', $section->value)
            ->whereHas('leader', fn ($query) => $query->active())
            ->orderBy('order_column')
            ->orderBy('id')
            ->get()
            ->map(fn (LeaderSection $assignment): array => [
                'name' => $assignment->leader->name,
                'scout_name' => $assignment->leader->scout_name,
                'bio' => Str::markdown((string) $assignment->leader->bio, [
                    'allow_unsafe_links' => false,
                    'html_input' => 'strip',
                ]),
                'fun_fact' => $assignment->leader->fun_fact,
                'photo' => $assignment->leader->photoUrl(),
            ])
            ->all();

        return $data;
    }
}
