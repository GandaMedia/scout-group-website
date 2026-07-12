<?php

namespace App\Filament\Resources\ContactEnquiries\Tables;

use App\Models\ContactEnquiry;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactEnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('message')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->placeholder('Pending review')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('markReviewed')
                    ->label('Mark reviewed')
                    ->icon(Heroicon::CheckBadge)
                    ->visible(fn (ContactEnquiry $record): bool => $record->reviewed_at === null)
                    ->action(function (ContactEnquiry $record): void {
                        $record->markReviewed();

                        Notification::make()
                            ->title('Enquiry marked as reviewed')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
            ]);
    }
}
