<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Enums\MenuItemType;
use App\Models\Page;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->columnSpanFull(),
                Select::make('menu_id')
                    ->relationship('menu', 'name')
                    ->required(),

                Select::make('parent_id')
                    ->live()
                    ->relationship('parent', 'name' , ignoreRecord: true),
                Select::make('type')
                    ->options(MenuItemType::class)
                    ->live()
                    ->required(),

                TextInput::make('link')->url()
                    ->required(fn($get) => $get('type') == MenuItemType::LINK)
                    ->visible(fn($get) => $get('type') == MenuItemType::LINK),
                MorphToSelect::make('menuable')
                    ->types([
                        MorphToSelect\Type::make(Page::class)
                            ->titleAttribute('title'),
                    ])
                    ->label('Page')
                    ->visible()
                    ->required(fn($get) => $get('type') == MenuItemType::MODEL)
                    ->visible(fn($get) => $get('type') == MenuItemType::MODEL)
            ]);
    }
}
