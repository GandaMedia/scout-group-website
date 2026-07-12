<?php

namespace App\Filament\Resources\WaitingListEntries\Schemas;

use App\Models\WaitingListEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WaitingListEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Child details')
                    ->schema([
                        TextEntry::make('full_name')
                            ->state(fn (WaitingListEntry $record): string => $record->fullName()),
                        TextEntry::make('section_label')
                            ->state(fn (WaitingListEntry $record): string => $record->section_label),
                        TextEntry::make('date_of_birth')
                            ->date(),
                        TextEntry::make('age')
                            ->state(fn (WaitingListEntry $record): ?string => $record->ageLabel()),
                    ])
                    ->columns(2),
                Section::make('Parent / carer details')
                    ->schema([
                        TextEntry::make('parent_name'),
                        TextEntry::make('parent_email')
                            ->copyable(),
                        TextEntry::make('parent_phone')
                            ->copyable(),
                        TextEntry::make('postcode'),
                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('No notes supplied.'),
                    ])
                    ->columns(2),
                Section::make('Sync details')
                    ->schema([
                        TextEntry::make('sync_status')
                            ->badge(),
                        IconEntry::make('is_possible_duplicate')
                            ->label('Duplicate flag')
                            ->boolean(),
                        TextEntry::make('duplicate_reason')
                            ->placeholder('No duplicate flag set.'),
                        TextEntry::make('osm_scout_id')
                            ->placeholder('Not synced yet.'),
                        TextEntry::make('submitted_at')
                            ->dateTime(),
                        TextEntry::make('sync_attempted_at')
                            ->dateTime()
                            ->placeholder('No attempts yet.'),
                        TextEntry::make('synced_at')
                            ->dateTime()
                            ->placeholder('Not synced yet.'),
                        TextEntry::make('last_error')
                            ->columnSpanFull()
                            ->placeholder('No error recorded.'),
                        TextEntry::make('last_payload')
                            ->state(fn (WaitingListEntry $record): ?string => $record->last_payload !== null ? json_encode($record->last_payload, JSON_PRETTY_PRINT) : null)
                            ->columnSpanFull()
                            ->placeholder('No payload built yet.')
                            ->copyable(),
                        TextEntry::make('osm_response')
                            ->state(fn (WaitingListEntry $record): ?string => $record->osm_response !== null ? json_encode($record->osm_response, JSON_PRETTY_PRINT) : null)
                            ->columnSpanFull()
                            ->placeholder('No OSM response stored yet.')
                            ->copyable(),
                    ])
                    ->columns(2),
            ]);
    }
}
