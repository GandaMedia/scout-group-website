<?php

namespace App\Http\Controllers;

use App\Enums\Section;
use App\Models\CalendarEvent;
use Inertia\Inertia;
use Inertia\Response;

class CalendarEventController extends Controller
{
    public function __invoke(CalendarEvent $event): Response
    {
        return Inertia::render('Calendar/Event', [
            'event' => [
                'title' => $event->title,
                'slug' => $event->slug,
                'startsAt' => $event->starts_at->toIso8601String(),
                'endsAt' => $event->ends_at->toIso8601String(),
                'allDay' => $event->all_day,
                'content' => $event->content,
                'sections' => collect($event->sections ?? [])
                    ->map(static fn (Section $section): string => $section->getLabel() ?? $section->value)
                    ->values()
                    ->all(),
                'image' => $event->getFirstMediaUrl('image', 'large') ?: null,
            ],
        ]);
    }
}
