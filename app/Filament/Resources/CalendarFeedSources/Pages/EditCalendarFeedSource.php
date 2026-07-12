<?php

namespace App\Filament\Resources\CalendarFeedSources\Pages;

use App\Filament\Resources\CalendarFeedSources\CalendarFeedSourceResource;
use App\Services\CalendarFeeds\CalendarFeedSyncService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCalendarFeedSource extends EditRecord
{
    protected static string $resource = CalendarFeedSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncNow')
                ->label('Sync now')
                ->icon(Heroicon::ArrowPath)
                ->disabled(fn (): bool => ! $this->getRecord()->is_enabled)
                ->action(function (): void {
                    $result = app(CalendarFeedSyncService::class)->syncSource($this->getRecord());

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
            DeleteAction::make(),
        ];
    }
}
