<?php

namespace App\Filament\Resources\WaitingListEntries\Schemas;

use App\Enums\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;

class WaitingListEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaSection::make('Child details')
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('date_of_birth')
                            ->required()
                            ->native(false),
                        Select::make('section_slug')
                            ->label('Section')
                            ->options(Section::optionsBySlug())
                            ->required(),
                    ])
                    ->columns(2),
                SchemaSection::make('Parent / carer details')
                    ->schema([
                        TextInput::make('parent_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('parent_email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('parent_phone')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('postcode')
                            ->required()
                            ->maxLength(32),
                        Textarea::make('notes')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
