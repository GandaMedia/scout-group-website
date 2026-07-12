<?php

namespace App\Http\Controllers;

use App\Enums\Section;
use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __invoke(Request $request, ?int $year = null, ?int $month = null): Response
    {
        $displayMonth = $this->resolveDisplayMonth($year, $month);
        $gridStartsAt = $displayMonth->startOfMonth()->startOfWeek(CarbonInterface::MONDAY);
        $gridEndsAt = $displayMonth->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY);
        $activeSections = $this->resolveActiveSections($request);
        $availableSections = $this->availableSectionsForGrid($gridStartsAt, $gridEndsAt);

        return Inertia::render('Calendar/Show', [
            'month' => $this->buildMonth($displayMonth),
            'filters' => [
                'availableSections' => $availableSections,
                'activeSections' => array_map(
                    static fn (Section $section): string => $section->value,
                    $activeSections,
                ),
            ],
            'days' => $this->buildDays(
                $displayMonth,
                $gridStartsAt,
                $gridEndsAt,
                $this->eventsByDate($gridStartsAt, $gridEndsAt, $activeSections),
            ),
        ]);
    }

    private function resolveDisplayMonth(?int $year, ?int $month): CarbonImmutable
    {
        $timezone = config('app.timezone');
        $currentMonth = CarbonImmutable::now($timezone)->month;

        if ($year === null && $month === null) {
            return CarbonImmutable::now($timezone)->startOfMonth();
        }

        if ($year !== null && $month === null) {
            return CarbonImmutable::createSafe($year, $currentMonth, 1, 0, 0, 0, $timezone)->startOfMonth();
        }

        abort_if($year === null, 404);
        abort_if($month < 1 || $month > 12, 404);

        return CarbonImmutable::createSafe($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
    }

    /**
     * @return array{label: string, year: int, month: int, today: string}
     */
    private function buildMonth(CarbonImmutable $displayMonth): array
    {
        return [
            'label' => $displayMonth->format('F Y'),
            'year' => $displayMonth->year,
            'month' => $displayMonth->month,
            'today' => CarbonImmutable::now(config('app.timezone'))->toDateString(),
        ];
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $eventsByDate
     * @return list<array<string, mixed>>
     */
    private function buildDays(
        CarbonImmutable $displayMonth,
        CarbonImmutable $gridStartsAt,
        CarbonImmutable $gridEndsAt,
        array $eventsByDate,
    ): array {
        $today = CarbonImmutable::now(config('app.timezone'))->toDateString();
        $days = [];

        for ($date = $gridStartsAt; $date->lte($gridEndsAt); $date = $date->addDay()) {
            $days[] = [
                'date' => $date->toDateString(),
                'dayNumber' => $date->day,
                'isCurrentMonth' => $date->isSameMonth($displayMonth),
                'isToday' => $date->toDateString() === $today,
                'events' => $eventsByDate[$date->toDateString()] ?? [],
            ];
        }

        return $days;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function eventsByDate(
        CarbonImmutable $gridStartsAt,
        CarbonImmutable $gridEndsAt,
        array $activeSections,
    ): array {
        $eventsByDate = [];

        $this->calendarEventsForGrid($gridStartsAt, $gridEndsAt, $activeSections)
            ->each(function (CalendarEvent $event) use (&$eventsByDate, $gridStartsAt, $gridEndsAt): void {
                $timezone = config('app.timezone');
                $startsAt = CarbonImmutable::instance($event->starts_at)->setTimezone($timezone);
                $endsAt = CarbonImmutable::instance($event->ends_at)->setTimezone($timezone);
                $visibleStartsAt = $startsAt->startOfDay()->max($gridStartsAt);
                $visibleEndsAt = $endsAt->startOfDay()->min($gridEndsAt);

                for ($date = $visibleStartsAt; $date->lte($visibleEndsAt); $date = $date->addDay()) {
                    $eventsByDate[$date->toDateString()][] = $this->buildEvent($event, $date, $startsAt, $endsAt);
                }
            });

        return $eventsByDate;
    }

    /**
     * @return Collection<int, CalendarEvent>
     */
    private function calendarEventsForGrid(
        CarbonImmutable $gridStartsAt,
        CarbonImmutable $gridEndsAt,
        array $activeSections,
    ): Collection {
        return CalendarEvent::query()
            ->select([
                'id',
                'slug',
                'title',
                'starts_at',
                'ends_at',
                'content',
                'all_day',
                'sections',
            ])
            ->where('starts_at', '<=', $gridEndsAt->endOfDay())
            ->where('ends_at', '>=', $gridStartsAt->startOfDay())
            ->when(
                $activeSections !== [],
                function ($query) use ($activeSections): void {
                    $query->where(function ($sectionQuery) use ($activeSections): void {
                        foreach ($activeSections as $section) {
                            $sectionQuery->orWhereJsonContains('sections', $section->value);
                        }
                    });
                },
            )
            ->orderBy('starts_at')
            ->orderBy('ends_at')
            ->get();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function availableSectionsForGrid(
        CarbonImmutable $gridStartsAt,
        CarbonImmutable $gridEndsAt,
    ): array {
        return $this->calendarEventsForGrid($gridStartsAt, $gridEndsAt, [])
            ->flatMap(static fn (CalendarEvent $event): array => $event->sections ?? [])
            ->filter()
            ->unique(static fn (Section $section): string => $section->value)
            ->sortBy(static fn (Section $section): int => array_search($section, Section::cases(), true))
            ->map(static fn (Section $section): array => [
                'value' => $section->value,
                'label' => $section->getLabel() ?? $section->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<Section>
     */
    private function resolveActiveSections(Request $request): array
    {
        $requestedSections = $request->query('sections', []);

        if (is_string($requestedSections)) {
            $requestedSections = [$requestedSections];
        }

        if (! is_array($requestedSections)) {
            return [];
        }

        $sectionsByValue = collect(Section::cases())
            ->mapWithKeys(static fn (Section $section): array => [$section->value => $section]);

        return collect($requestedSections)
            ->filter(static fn (mixed $section): bool => is_string($section))
            ->map(static fn (string $section): string => trim($section))
            ->filter()
            ->map(static fn (string $section) => $sectionsByValue->get($section))
            ->filter()
            ->unique(static fn (Section $section): string => $section->value)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEvent(
        CalendarEvent $event,
        CarbonImmutable $date,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
    ): array {
        $spansMultipleDays = $startsAt->toDateString() !== $endsAt->toDateString();
        $startsOnDay = $date->isSameDay($startsAt);
        $endsOnDay = $date->isSameDay($endsAt);

        return [
            'id' => $event->getKey(),
            'slug' => $event->slug,
            'title' => $event->title,
            'startsAt' => $startsAt->toIso8601String(),
            'endsAt' => $endsAt->toIso8601String(),
            'allDay' => $event->all_day,
            'content' => $event->content,
            'sections' => collect($event->sections ?? [])
                ->map(static fn (Section $section): string => $section->value)
                ->values()
                ->all(),
            'timeLabel' => $this->eventTimeLabel($event->all_day, $spansMultipleDays, $startsOnDay, $endsOnDay, $startsAt, $endsAt),
            'listTimeLabel' => $this->eventListTimeLabel($event->all_day, $spansMultipleDays, $startsOnDay, $endsOnDay, $startsAt, $endsAt),
            'spansMultipleDays' => $spansMultipleDays,
            'startsOnDay' => $startsOnDay,
            'endsOnDay' => $endsOnDay,
        ];
    }

    private function eventTimeLabel(
        bool $allDay,
        bool $spansMultipleDays,
        bool $startsOnDay,
        bool $endsOnDay,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
    ): string {
        if ($allDay) {
            return 'All day';
        }

        if (! $spansMultipleDays) {
            return $startsAt->format('g:i A');
        }

        if ($startsOnDay) {
            return 'Starts '.$startsAt->format('g:i A');
        }

        if ($endsOnDay) {
            return 'Ends '.$endsAt->format('g:i A');
        }

        return 'Continues';
    }

    private function eventListTimeLabel(
        bool $allDay,
        bool $spansMultipleDays,
        bool $startsOnDay,
        bool $endsOnDay,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
    ): string {
        if ($allDay) {
            return 'All day';
        }

        if (! $spansMultipleDays) {
            return $startsAt->format('g:i A').' - '.$endsAt->format('g:i A');
        }

        if ($startsOnDay) {
            return 'Starts '.$startsAt->format('g:i A');
        }

        if ($endsOnDay) {
            return 'Ends '.$endsAt->format('g:i A');
        }

        return 'All day';
    }
}
