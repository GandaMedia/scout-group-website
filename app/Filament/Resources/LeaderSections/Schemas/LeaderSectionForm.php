<?php

namespace App\Filament\Resources\LeaderSections\Schemas;

use App\Enums\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeaderSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('leader_id')
                    ->relationship('leader', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('section')
                    ->options(Section::class)
                    ->native(false)
                    ->required(),

                TextInput::make('order_column')
                    ->label('Order')
                    ->numeric()
                    ->minValue(0),
            ]);
    }
}
