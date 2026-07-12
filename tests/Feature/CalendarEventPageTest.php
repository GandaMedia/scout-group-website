<?php

use App\Enums\Section;
use App\Models\CalendarEvent;
use Inertia\Testing\AssertableInertia as Assert;

it('renders a shareable calendar event page', function () {
    $event = CalendarEvent::factory()->create([
        'title' => 'Group camp briefing',
        'content' => 'Meet at headquarters with a packed bag.',
        'starts_at' => '2026-09-12 18:30:00',
        'ends_at' => '2026-09-12 20:00:00',
        'sections' => [Section::SCOUTS, Section::CUBS],
    ]);

    $this->get(route('calendar.events.show', $event))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calendar/Event')
            ->where('event.title', 'Group camp briefing')
            ->where('event.slug', $event->slug)
            ->where('event.content', 'Meet at headquarters with a packed bag.')
            ->where('event.sections', ['Scouts', 'Cubs']));
});

it('returns not found for an unknown calendar event', function () {
    $this->get('/calendar/event/not-an-event')->assertNotFound();
});
