<?php

namespace App\Filament\Resources\CalendarFeedSources\Pages;

use App\Filament\Resources\CalendarFeedSources\CalendarFeedSourceResource;
use App\Services\CalendarFeeds\CalendarFeedSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCalendarFeedSources extends ListRecords
{
    protected static string $resource = CalendarFeedSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncAll')
                ->label('Sync all feeds')
                ->icon(Heroicon::ArrowPath)
                ->action(function (): void {
                    $result = app(CalendarFeedSyncService::class)->syncAll();

                    Notification::make()
                        ->title('Calendar feeds synced')
                        ->body(sprintf(
                            'Processed %d feed(s), %d failed, %d event link(s) imported, %d stale link(s) removed.',
                            $result->sourcesProcessed,
                            $result->sourcesFailed,
                            $result->eventsImported,
                            $result->eventsRemoved,
                        ))
                        ->color($result->sourcesFailed === 0 ? 'success' : 'warning')
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
