<?php

namespace App\Filament\Resources\ContactEnquiries\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactEnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('email')
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('message')
                    ->disabled()
                    ->dehydrated(false)
                    ->rows(8)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
