<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use App\Models\CalendarEvent;
use Filament\Actions\Action;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MyCalendarWidget extends CalendarWidget
{
    protected bool $eventClickEnabled = true;

    public function getHeaderActions(): array
    {
        return [
            Action::make('addEvent')
                ->label('Add event')
                ->icon(Heroicon::Plus)
                ->url(CalendarEventResource::getUrl('create')),
        ];
    }

    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        return CalendarEvent::query()
            ->whereDate('ends_at', '>=', $info->start)
            ->whereDate('starts_at', '<=', $info->end);
    }

    protected function onEventClick(EventClickInfo $info, Model $event, ?string $action = null): void
    {
        $url = CalendarEventResource::getUrl('edit', ['record' => $event]);

        $this->redirect($url, navigate: FilamentView::hasSpaMode($url));
    }
}
