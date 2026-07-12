<?php

namespace App\Filament\Resources\Leaders\Schemas;

use App\Enums\Section;
use App\Models\Leader;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeaderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('scout_name')
                    ->label('Scout name')
                    ->maxLength(255),

                Toggle::make('is_active')
                    ->label('Show publicly')
                    ->default(true)
                    ->required(),

                SpatieMediaLibraryFileUpload::make('photo')
                    ->collection('photo')
                    ->image()
                    ->required(fn (?Leader $record): bool => ! $record?->hasMedia('photo'))
                    ->columnSpanFull(),

                MarkdownEditor::make('bio')
                    ->required()
                    ->columnSpanFull(),

                Textarea::make('fun_fact')
                    ->label('Fun fact')
                    ->rows(3)
                    ->columnSpanFull(),

                Repeater::make('sectionAssignments')
                    ->label('Sections')
                    ->relationship()
                    ->schema([
                        Select::make('section')
                            ->options(Section::class)
                            ->required()
                            ->distinct()
                            ->native(false),
                    ])
                    ->orderColumn('order_column')
                    ->addActionLabel('Assign section')
                    ->reorderable()
                    ->columnSpanFull(),
            ]);
    }
}
