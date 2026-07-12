<?php

use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use App\Filament\Widgets\MyCalendarWidget;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('is registered on the admin panel dashboard', function () {
    $panel = (new AdminPanelProvider(app()))->panel(Panel::make());

    expect($panel->getWidgets())->toContain(MyCalendarWidget::class);
});

it('has a header action to create calendar events', function () {
    Livewire::test(MyCalendarWidget::class)
        ->assertActionExists('addEvent')
        ->assertActionHasLabel('addEvent', 'Add event')
        ->assertActionHasUrl('addEvent', CalendarEventResource::getUrl('create'))
        ->assertActionShouldNotOpenUrlInNewTab('addEvent');
});

it('enables clicking calendar events', function () {
    expect(Livewire::test(MyCalendarWidget::class)->instance()->isEventClickEnabled())->toBeTrue();
});

it('redirects to the calendar event edit page when an event is clicked', function () {
    $event = CalendarEvent::factory()->create();

    Livewire::test(MyCalendarWidget::class)
        ->call('onEventClickJs', [
            'event' => [
                'title' => $event->title,
                'start' => $event->starts_at->toIso8601String(),
                'end' => $event->ends_at->toIso8601String(),
                'allDay' => $event->all_day,
                'styles' => [],
                'classNames' => [],
                'resourceIds' => [],
                'extendedProps' => $event->toCalendarEvent()->getExtendedProps(),
                'display' => 'auto',
            ],
            'view' => [
                'type' => 'dayGridMonth',
                'title' => 'April 2026',
                'currentStart' => now()->startOfMonth()->toIso8601String(),
                'currentEnd' => now()->endOfMonth()->toIso8601String(),
                'activeStart' => now()->startOfMonth()->toIso8601String(),
                'activeEnd' => now()->endOfMonth()->toIso8601String(),
            ],
            'tzOffset' => 0,
        ])
        ->assertRedirect(CalendarEventResource::getUrl('edit', ['record' => $event]));
});
