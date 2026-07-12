<?php

namespace App\Filament\Resources\ContactEnquiries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactEnquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact enquiry')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->copyable(),
                        TextEntry::make('submitted_at')
                            ->dateTime(),
                        TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->placeholder('Not reviewed yet'),
                        TextEntry::make('message')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
