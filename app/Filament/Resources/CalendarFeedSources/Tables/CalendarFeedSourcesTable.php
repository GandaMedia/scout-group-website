<?php

namespace App\Filament\Resources\CalendarFeedSources\Tables;

use App\Models\CalendarFeedSource;
use App\Services\CalendarFeeds\CalendarFeedSyncService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CalendarFeedSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('section')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                TextColumn::make('last_sync_status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('last_synced_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_event_count')
                    ->label('Events')
                    ->numeric()
                    ->default(0),
                TextColumn::make('last_sync_error')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('syncNow')
                    ->label('Sync now')
                    ->icon(Heroicon::ArrowPath)
                    ->disabled(fn (CalendarFeedSource $record): bool => ! $record->is_enabled)
                    ->action(function (CalendarFeedSource $record): void {
                        $result = app(CalendarFeedSyncService::class)->syncSource($record);

                        Notification::make()
                            ->title('Feed sync complete')
                            ->body(sprintf(
                                'Processed %d feed, %d failed, %d event link(s) imported, %d stale link(s) removed.',
                                $result->sourcesProcessed,
                                $result->sourcesFailed,
                                $result->eventsImported,
                                $result->eventsRemoved,
                            ))
                            ->color($result->sourcesFailed === 0 ? 'success' : 'warning')
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
