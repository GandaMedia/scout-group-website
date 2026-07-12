<?php

namespace App\Filament\Resources\CalendarEvents\Schemas;

use App\Enums\Section;
use App\Models\CalendarEvent;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CalendarEventForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSyncedRecord = static fn (?CalendarEvent $record): bool => $record?->isSynced() ?? false;

        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->disabled($isSyncedRecord)
                    ->helperText(fn (?CalendarEvent $record): ?string => $record?->isSynced() ? 'Synced events are managed by OSM feed sync and cannot be edited here.' : null),
                TextInput::make('slug')
                    ->helperText('Leave blank to generate from the title.')
                    ->maxLength(255)
                    ->required(fn (string $operation): bool => $operation === 'edit')
                    ->unique(CalendarEvent::class, 'slug', ignoreRecord: true)
                    ->disabled($isSyncedRecord),
                ToggleButtons::make('all_day')
                    ->boolean(trueLabel: 'All day', falseLabel: 'Timed')
                    ->default(false)
                    ->grouped()
                    ->live()
                    ->required()
                    ->disabled($isSyncedRecord),
                DateTimePicker::make('starts_at')
                    ->live()
                    ->seconds(false)
                    ->time(fn (Get $get): bool => ! $get('all_day'))
                    ->maxDate(fn (Get $get): mixed => $get('ends_at'))
                    ->before('ends_at')
                    ->required()
                    ->disabled($isSyncedRecord),
                DateTimePicker::make('ends_at')
                    ->live()
                    ->seconds(false)
                    ->time(fn (Get $get): bool => ! $get('all_day'))
                    ->minDate(fn (Get $get): mixed => $get('starts_at'))
                    ->after('starts_at')
                    ->required()
                    ->disabled($isSyncedRecord),

                MarkdownEditor::make('content')
                    ->columnSpanFull()
                    ->disabled($isSyncedRecord),
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection('image')
                    ->image()
                    ->columnSpanFull()
                    ->disabled($isSyncedRecord),
                Select::make('sections')
                    ->multiple()
                    ->options(Section::class)
                    ->columnSpanFull()
                    ->disabled($isSyncedRecord),
            ]);
    }
}
