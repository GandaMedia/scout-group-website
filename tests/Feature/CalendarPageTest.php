<?php

use App\Enums\Section;
use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('calendar defaults to the current month when no parameters are provided', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 09:30:00'));

    CalendarEvent::factory()->create([
        'title' => 'District planning night',
        'starts_at' => '2026-04-23 19:00:00',
        'ends_at' => '2026-04-23 21:00:00',
        'all_day' => false,
        'sections' => [Section::SCOUTS],
    ]);

    $this->get(route('calendar'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calendar/Show')
            ->where('month.year', 2026)
            ->where('month.month', 4)
            ->where('month.label', 'April 2026')
            ->where('month.today', '2026-04-23')
            ->where('filters.activeSections', [])
            ->where('filters.availableSections', [[
                'value' => Section::SCOUTS->value,
                'label' => Section::SCOUTS->value,
            ]])
            ->has('days', 35)
            ->where('days.24.date', '2026-04-23')
            ->where('days.24.isToday', true)
            ->where('days.24.events.0.title', 'District planning night')
            ->where('days.24.events.0.sections', [Section::SCOUTS->value])
        );
});

test('calendar year-only paths default to the current month within that year', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 09:30:00'));

    $this->get('/calendar/2028')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calendar/Show')
            ->where('month.year', 2028)
            ->where('month.month', 4)
            ->where('month.label', 'April 2028')
        );
});

test('calendar loads only the events visible for the requested month grid', function () {
    CalendarEvent::factory()->create([
        'title' => 'Grid start event',
        'starts_at' => '2026-11-30 18:00:00',
        'ends_at' => '2026-11-30 19:30:00',
        'all_day' => false,
        'sections' => [Section::BEAVERS],
    ]);

    CalendarEvent::factory()->create([
        'title' => 'Winter camp',
        'starts_at' => '2026-12-12 09:00:00',
        'ends_at' => '2026-12-14 15:00:00',
        'all_day' => false,
        'sections' => [Section::SCOUTS, Section::CUBS],
    ]);

    CalendarEvent::factory()->create([
        'title' => 'Grid end event',
        'starts_at' => '2027-01-03 10:00:00',
        'ends_at' => '2027-01-03 11:00:00',
        'all_day' => false,
        'sections' => [Section::SQUIRRELS],
    ]);

    CalendarEvent::factory()->create([
        'title' => 'Outside visible grid',
        'starts_at' => '2027-01-04 18:00:00',
        'ends_at' => '2027-01-04 19:00:00',
        'all_day' => false,
        'sections' => [Section::SCOUTS],
    ]);

    $this->get(route('calendar', ['year' => 2026, 'month' => 12]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calendar/Show')
            ->where('month.year', 2026)
            ->where('month.month', 12)
            ->where('month.label', 'December 2026')
            ->has('days', 35)
            ->where('days.0.date', '2026-11-30')
            ->where('days.0.events.0.title', 'Grid start event')
            ->where('days.12.date', '2026-12-12')
            ->where('days.12.events.0.title', 'Winter camp')
            ->where('days.13.events.0.timeLabel', 'Continues')
            ->where('days.14.events.0.timeLabel', 'Ends 3:00 PM')
            ->where('days.34.date', '2027-01-03')
            ->where('days.34.events.0.title', 'Grid end event')
            ->where('days', function ($days): bool {
                return collect($days)
                    ->flatMap(static fn (array $day): array => $day['events'])
                    ->pluck('title')
                    ->doesntContain('Outside visible grid');
            })
        );
});

test('calendar rejects invalid month paths', function () {
    $this->get('/calendar/2026/13')->assertNotFound();
});

test('calendar filters events by selected sections', function () {
    CalendarEvent::factory()->create([
        'title' => 'Beavers craft night',
        'starts_at' => '2026-12-12 18:00:00',
        'ends_at' => '2026-12-12 19:30:00',
        'all_day' => false,
        'sections' => [Section::BEAVERS],
    ]);

    CalendarEvent::factory()->create([
        'title' => 'Scouts hike planning',
        'starts_at' => '2026-12-12 20:00:00',
        'ends_at' => '2026-12-12 21:00:00',
        'all_day' => false,
        'sections' => [Section::SCOUTS],
    ]);

    CalendarEvent::factory()->create([
        'title' => 'Group camp briefing',
        'starts_at' => '2026-12-13 19:00:00',
        'ends_at' => '2026-12-13 20:30:00',
        'all_day' => false,
        'sections' => [Section::SCOUTS, Section::CUBS],
    ]);

    $this->get(route('calendar', [
        'year' => 2026,
        'month' => 12,
        'sections' => [Section::SCOUTS->value],
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calendar/Show')
            ->where('filters.activeSections', [Section::SCOUTS->value])
            ->where('filters.availableSections', [
                [
                    'value' => Section::SCOUTS->value,
                    'label' => Section::SCOUTS->value,
                ],
                [
                    'value' => Section::CUBS->value,
                    'label' => Section::CUBS->value,
                ],
                [
                    'value' => Section::BEAVERS->value,
                    'label' => Section::BEAVERS->value,
                ],
            ])
            ->where('days', function ($days): bool {
                $titles = collect($days)
                    ->flatMap(static fn (array $day): array => $day['events'])
                    ->pluck('title');

                return $titles->contains('Scouts hike planning')
                    && $titles->contains('Group camp briefing')
                    && $titles->doesntContain('Beavers craft night');
            })
        );
});
