<?php

namespace App\Filament\Resources\CalendarFeedSources\Schemas;

use App\Enums\Section;
use App\Models\CalendarFeedSource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;

class CalendarFeedSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaSection::make('Feed')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('section')
                                ->options(Section::class)
                                ->required()
                                ->unique(CalendarFeedSource::class, 'section', ignoreRecord: true)
                                ->columnSpan(1),
                            Toggle::make('is_enabled')
                                ->label('Enabled')
                                ->default(true)
                                ->required()
                                ->columnSpan(1),
                            TextInput::make('feed_url')
                                ->label('OSM feed URL')
                                ->url()
                                ->required()
                                ->columnSpanFull()
                                ->helperText('Paste the section-specific iCalendar feed URL from Online Scout Manager.'),
                        ]),
                    ]),
                SchemaSection::make('Sync status')
                    ->schema([
                        TextInput::make('last_sync_status')
                            ->label('Status')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?CalendarFeedSource $record): string => $record?->last_sync_status?->getLabel() ?? 'Never synced'),
                        TextInput::make('last_synced_at')
                            ->label('Last synced at')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?CalendarFeedSource $record): string => $record?->last_synced_at?->toDayDateTimeString() ?? 'Not yet synced'),
                        TextInput::make('last_event_count')
                            ->label('Last imported event count')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?CalendarFeedSource $record): string => (string) ($record?->last_event_count ?? 0)),
                        TextInput::make('last_sync_error')
                            ->label('Last sync error')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?CalendarFeedSource $record): string => $record?->last_sync_error ?: 'None'),
                    ])
                    ->columns(2)
                    ->visible(fn (?CalendarFeedSource $record): bool => $record !== null),
            ]);
    }
}
