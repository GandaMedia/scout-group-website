<script setup lang="ts">
import CalendarController from '@/actions/App/Http/Controllers/CalendarController';
import CalendarEventController from '@/actions/App/Http/Controllers/CalendarEventController';
import BeaversLogo from '@/components/logos/BeaversLogo.vue';
import CubsLogo from '@/components/logos/CubsLogo.vue';
import ScoutsLogo from '@/components/logos/ScoutsLogo.vue';
import SquirrelsLogo from '@/components/logos/SquirrelsLogo.vue';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    ClockIcon,
} from '@heroicons/vue/20/solid';
import { Link, router } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed, ref } from 'vue';

type CalendarEvent = {
    id: number;
    slug: string;
    title: string;
    startsAt: string;
    endsAt: string;
    allDay: boolean;
    content: string | null;
    sections: string[];
    timeLabel: string;
    listTimeLabel: string;
    spansMultipleDays: boolean;
    startsOnDay: boolean;
    endsOnDay: boolean;
};

type CalendarDay = {
    date: string;
    dayNumber: number;
    isCurrentMonth: boolean;
    isToday: boolean;
    events: CalendarEvent[];
};

type CalendarWeekBar = {
    id: number;
    event: CalendarEvent;
    title: string;
    row: number;
    startColumn: number;
    span: number;
    startsInWeek: boolean;
    endsInWeek: boolean;
};

type CalendarWeek = {
    days: CalendarDay[];
    bars: CalendarWeekBar[];
    barRows: number;
    trackRows: number;
};

const props = defineProps<{
    month: {
        label: string;
        year: number;
        month: number;
        today: string;
    };
    filters: {
        availableSections: Array<{
            value: string;
            label: string;
        }>;
        activeSections: string[];
    };
    days: CalendarDay[];
}>();

const selectedDate = ref(defaultSelectedDate());
const selectedEvent = ref<CalendarEvent | null>(null);
const activeSections = computed(() => props.filters.activeSections);

const selectedDay = computed(() => {
    return (
        props.days.find((day) => day.date === selectedDate.value) ??
        props.days.find((day) => day.isCurrentMonth) ??
        props.days[0]
    );
});

const events = computed(() => selectedDay.value?.events ?? []);
const monthDateTime = computed(
    () => `${props.month.year}-${String(props.month.month).padStart(2, '0')}`,
);
const hasActiveSectionFilters = computed(() => activeSections.value.length > 0);
const previousMonth = computed(() => adjacentMonth(-1));
const nextMonth = computed(() => adjacentMonth(1));
const weeks = computed<CalendarWeek[]>(() => buildWeeks(props.days));
const selectedEventDateLabel = computed(() => {
    if (selectedEvent.value === null) {
        return '';
    }

    const startsAt = new Date(selectedEvent.value.startsAt);
    const endsAt = new Date(selectedEvent.value.endsAt);

    if (selectedEvent.value.allDay) {
        return startsAt.toLocaleDateString('en-GB', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }

    const startDate = startsAt.toLocaleDateString('en-GB', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
    const endDate = endsAt.toLocaleDateString('en-GB', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    if (startDate === endDate) {
        return startDate;
    }

    return `${startDate} to ${endDate}`;
});

const sectionStyles: Record<
    string,
    {
        barClass: string;
        badgeClass: string;
        dotClass: string;
        listAccentClass: string;
        logoClass: string;
    }
> = {
    Squirrels: {
        barClass: 'bg-scout-red text-white',
        badgeClass: 'bg-scout-red text-white dark:bg-scout-red/85',
        dotClass: 'bg-scout-red dark:bg-scout-red',
        listAccentClass: 'border-scout-red',
        logoClass: 'text-scout-red',
    },
    Beavers: {
        barClass: 'bg-scout-blue text-white',
        badgeClass: 'bg-scout-blue text-white dark:bg-scout-blue/85',
        dotClass: 'bg-scout-blue dark:bg-scout-blue',
        listAccentClass: 'border-scout-blue',
        logoClass: 'text-scout-blue',
    },
    Cubs: {
        barClass: 'bg-scout-green text-white',
        badgeClass: 'bg-scout-green text-white dark:bg-scout-green/85',
        dotClass: 'bg-scout-green dark:bg-scout-green',
        listAccentClass: 'border-scout-green',
        logoClass: 'text-scout-green',
    },
    Scouts: {
        barClass: 'bg-scout-forest-green text-white',
        badgeClass:
            'bg-scout-forest-green text-white dark:bg-scout-forest-green/85',
        dotClass: 'bg-scout-forest-green dark:bg-scout-forest-green',
        listAccentClass: 'border-scout-forest-green',
        logoClass: 'text-scout-forest-green',
    },
};

const fallbackSectionStyle = {
    barClass: 'bg-scout-purple text-white',
    badgeClass: 'bg-scout-purple text-white dark:bg-scout-purple/85',
    dotClass: 'bg-scout-purple dark:bg-scout-purple',
    listAccentClass: 'border-scout-purple',
    logoClass: 'text-scout-purple',
};

const sectionLogos: Record<string, Component> = {
    Squirrels: SquirrelsLogo,
    Beavers: BeaversLogo,
    Cubs: CubsLogo,
    Scouts: ScoutsLogo,
};

function defaultSelectedDate(): string {
    const today = props.days.find(
        (day) => day.date === props.month.today && day.isCurrentMonth,
    );

    if (today !== undefined) {
        return today.date;
    }

    return (
        props.days.find((day) => day.isCurrentMonth)?.date ?? props.days[0].date
    );
}

function adjacentMonth(offset: number): { year: number; month: number } {
    const date = new Date(
        props.month.year,
        props.month.month - 1 + offset,
        1,
        12,
    );

    return {
        year: date.getFullYear(),
        month: date.getMonth() + 1,
    };
}

function calendarQueryOptions(sections: string[] = activeSections.value) {
    if (sections.length === 0) {
        return undefined;
    }

    return {
        query: {
            sections,
        },
    };
}

function calendarMonthUrl(
    year?: number,
    month?: number,
    sections: string[] = activeSections.value,
): string {
    if (year === undefined || month === undefined) {
        return CalendarController.url(
            undefined,
            calendarQueryOptions(sections),
        );
    }

    return CalendarController.url(
        [year, month],
        calendarQueryOptions(sections),
    );
}

function prefetchMonth(year?: number, month?: number): void {
    router.prefetch(calendarMonthUrl(year, month));
}

function goToMonth(year?: number, month?: number): void {
    router.visit(calendarMonthUrl(year, month), {
        preserveScroll: true,
    });
}

function sectionFilterStyle(section: string, isActive: boolean): string[] {
    const style = sectionStyle(section);

    if (isActive) {
        return [style.barClass, 'border-transparent'];
    }

    return [
        'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/5',
    ];
}

function toggleSectionFilter(section: string): void {
    const nextSections = activeSections.value.includes(section)
        ? activeSections.value.filter(
              (activeSection) => activeSection !== section,
          )
        : [...activeSections.value, section];

    router.visit(
        calendarMonthUrl(props.month.year, props.month.month, nextSections),
        {
            preserveScroll: true,
        },
    );
}

function clearSectionFilters(): void {
    router.visit(calendarMonthUrl(props.month.year, props.month.month, []), {
        preserveScroll: true,
    });
}

function selectDay(date: string): void {
    selectedDate.value = date;
}

function openEvent(event: CalendarEvent): void {
    selectedEvent.value = event;
}

function updateEventDialog(isOpen: boolean): void {
    if (!isOpen) {
        selectedEvent.value = null;
    }
}

function sectionStyle(section: string) {
    return sectionStyles[section] ?? fallbackSectionStyle;
}

function eventStyle(event: CalendarEvent) {
    return sectionStyle(event.sections[0] ?? '');
}

function sectionLogo(section: string): Component | null {
    return sectionLogos[section] ?? null;
}

function buildWeeks(days: CalendarDay[]): CalendarWeek[] {
    const weeks: CalendarWeek[] = [];

    for (let index = 0; index < days.length; index += 7) {
        const weekDays = days.slice(index, index + 7);
        const bars = buildWeekBars(weekDays);

        weeks.push({
            days: weekDays,
            bars,
            barRows: bars.reduce(
                (max, bar) => Math.max(max, bar.row + 1),
                bars.length > 0 ? 1 : 0,
            ),
            trackRows: Math.max(
                bars.reduce((max, bar) => Math.max(max, bar.row + 1), 0),
                2,
            ),
        });
    }

    return weeks;
}

function buildWeekBars(days: CalendarDay[]): CalendarWeekBar[] {
    const uniqueEvents = new Map<
        number,
        {
            event: CalendarEvent;
            startIndex: number;
            endIndex: number;
            startsInWeek: boolean;
            endsInWeek: boolean;
        }
    >();

    days.forEach((day, dayIndex) => {
        day.events.forEach((event) => {
            const existing = uniqueEvents.get(event.id);

            if (existing === undefined) {
                uniqueEvents.set(event.id, {
                    event,
                    startIndex: dayIndex,
                    endIndex: dayIndex,
                    startsInWeek: event.startsOnDay,
                    endsInWeek: event.endsOnDay,
                });

                return;
            }

            existing.endIndex = dayIndex;
            existing.endsInWeek = event.endsOnDay;
        });
    });

    const rowEnds: number[] = [];

    return Array.from(uniqueEvents.values())
        .sort((first, second) => {
            if (first.startIndex !== second.startIndex) {
                return first.startIndex - second.startIndex;
            }

            const firstSpan = first.endIndex - first.startIndex;
            const secondSpan = second.endIndex - second.startIndex;

            if (firstSpan !== secondSpan) {
                return secondSpan - firstSpan;
            }

            return first.event.title.localeCompare(second.event.title);
        })
        .map((bar) => {
            let row = rowEnds.findIndex((end) => end < bar.startIndex);

            if (row === -1) {
                row = rowEnds.length;
                rowEnds.push(bar.endIndex);
            } else {
                rowEnds[row] = bar.endIndex;
            }

            return {
                id: bar.event.id,
                event: bar.event,
                title: bar.event.title,
                row,
                startColumn: bar.startIndex + 1,
                span: bar.endIndex - bar.startIndex + 1,
                startsInWeek: bar.startsInWeek,
                endsInWeek: bar.endsInWeek,
            };
        });
}
</script>

<template>
    <div class="lg:flex lg:h-full lg:flex-col">
        <header
            class="flex flex-col gap-4 border-b border-gray-200 py-4 lg:flex-none dark:border-white/10 dark:bg-gray-800/50"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <h1
                    class="text-xl font-extrabold text-scout-purple dark:text-white"
                >
                    <time :datetime="monthDateTime">{{ month.label }}</time>
                </h1>
                <div class="flex items-center">
                    <div
                        class="relative flex items-center rounded-md bg-white shadow-xs outline -outline-offset-1 outline-gray-300 md:items-stretch dark:bg-white/10 dark:shadow-none dark:outline-white/5"
                    >
                        <button
                            type="button"
                            class="flex h-9 w-12 items-center justify-center rounded-l-md pr-1 text-gray-400 hover:text-gray-500 focus:relative md:w-9 md:pr-0 md:hover:bg-gray-50 dark:hover:text-white dark:md:hover:bg-white/10"
                            @mouseenter="
                                prefetchMonth(
                                    previousMonth.year,
                                    previousMonth.month,
                                )
                            "
                            @focus="
                                prefetchMonth(
                                    previousMonth.year,
                                    previousMonth.month,
                                )
                            "
                            @click="
                                goToMonth(
                                    previousMonth.year,
                                    previousMonth.month,
                                )
                            "
                        >
                            <span class="sr-only">Previous month</span>
                            <ChevronLeftIcon
                                class="size-5"
                                aria-hidden="true"
                            />
                        </button>
                        <button
                            type="button"
                            class="hidden px-3.5 text-sm font-semibold text-gray-900 hover:bg-gray-50 focus:relative md:block dark:text-white dark:hover:bg-white/10"
                            @mouseenter="prefetchMonth()"
                            @focus="prefetchMonth()"
                            @click="goToMonth()"
                        >
                            Today
                        </button>
                        <span
                            class="relative -mx-px h-5 w-px bg-gray-300 md:hidden dark:bg-white/10"
                        ></span>
                        <button
                            type="button"
                            class="flex h-9 w-12 items-center justify-center rounded-r-md pl-1 text-gray-400 hover:text-gray-500 focus:relative md:w-9 md:pl-0 md:hover:bg-gray-50 dark:hover:text-white dark:md:hover:bg-white/10"
                            @mouseenter="
                                prefetchMonth(nextMonth.year, nextMonth.month)
                            "
                            @focus="
                                prefetchMonth(nextMonth.year, nextMonth.month)
                            "
                            @click="goToMonth(nextMonth.year, nextMonth.month)"
                        >
                            <span class="sr-only">Next month</span>
                            <ChevronRightIcon
                                class="size-5"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                </div>
            </div>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="section in filters.availableSections"
                        :key="section.value"
                        type="button"
                        :class="[
                            'inline-flex items-center gap-2 border px-3 py-2 text-sm font-semibold transition-colors',
                            ...sectionFilterStyle(
                                section.value,
                                activeSections.includes(section.value),
                            ),
                        ]"
                        @click="toggleSectionFilter(section.value)"
                    >
                        <component
                            :is="sectionLogo(section.value) ?? 'span'"
                            v-if="sectionLogo(section.value)"
                            class="h-4 w-auto shrink-0"
                            :class="
                                activeSections.includes(section.value)
                                    ? 'text-white'
                                    : sectionStyle(section.value).logoClass
                            "
                        />
                        <span v-if="sectionLogo(section.value)" class="sr-only">
                            {{ section.label }}
                        </span>
                        <span v-else>{{ section.label }}</span>
                    </button>
                </div>
                <button
                    v-if="hasActiveSectionFilters"
                    type="button"
                    class="inline-flex items-center justify-center border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/5"
                    @click="clearSectionFilters"
                >
                    Clear filter
                </button>
            </div>
        </header>
        <div
            class="shadow-sm ring-1 ring-black/5 lg:flex lg:flex-auto lg:flex-col dark:shadow-none dark:ring-white/5"
        >
            <div
                class="grid grid-cols-7 gap-px border-b border-gray-300 bg-gray-200 text-center text-xs/6 font-semibold text-gray-700 lg:flex-none dark:border-white/5 dark:bg-white/15 dark:text-gray-300"
            >
                <div class="flex justify-center bg-white py-2 dark:bg-gray-900">
                    <span>M</span>
                    <span class="sr-only sm:not-sr-only">on</span>
                </div>
                <div class="flex justify-center bg-white py-2 dark:bg-gray-900">
                    <span>T</span>
                    <span class="sr-only sm:not-sr-only">ue</span>
                </div>
                <div class="flex justify-center bg-white py-2 dark:bg-gray-900">
                    <span>W</span>
                    <span class="sr-only sm:not-sr-only">ed</span>
                </div>
                <div class="flex justify-center bg-white py-2 dark:bg-gray-900">
                    <span>T</span>
                    <span class="sr-only sm:not-sr-only">hu</span>
                </div>
                <div class="flex justify-center bg-white py-2 dark:bg-gray-900">
                    <span>F</span>
                    <span class="sr-only sm:not-sr-only">ri</span>
                </div>
                <div class="flex justify-center bg-white py-2 dark:bg-gray-900">
                    <span>S</span>
                    <span class="sr-only sm:not-sr-only">at</span>
                </div>
                <div class="flex justify-center bg-white py-2 dark:bg-gray-900">
                    <span>S</span>
                    <span class="sr-only sm:not-sr-only">un</span>
                </div>
            </div>
            <div
                class="flex bg-gray-200 text-xs/6 text-gray-700 lg:flex-auto dark:bg-white/10 dark:text-gray-300"
            >
                <div class="hidden w-full lg:flex lg:flex-col">
                    <div v-for="week in weeks" :key="week.days[0]?.date">
                        <div
                            class="grid grid-cols-7 gap-px"
                            :style="{
                                gridTemplateRows: `2.5rem repeat(${week.trackRows}, minmax(1.5rem, auto))`,
                            }"
                        >
                            <div
                                v-for="(day, dayIndex) in week.days"
                                :key="day.date"
                                :data-is-current-month="
                                    day.isCurrentMonth ? '' : undefined
                                "
                                :data-is-today="day.isToday ? '' : undefined"
                                class="group relative bg-gray-50 px-3 py-2 text-gray-500 data-is-current-month:bg-white dark:bg-gray-900 dark:text-gray-400 dark:not-data-is-current-month:before:pointer-events-none dark:not-data-is-current-month:before:absolute dark:not-data-is-current-month:before:inset-0 dark:not-data-is-current-month:before:bg-gray-800/50 dark:data-is-current-month:bg-gray-900"
                                :style="{
                                    gridColumn: String(dayIndex + 1),
                                    gridRow: `1 / span ${week.trackRows + 1}`,
                                }"
                            >
                                <time
                                    :datetime="day.date"
                                    class="relative group-not-data-is-current-month:opacity-75 in-data-is-today:flex in-data-is-today:size-6 in-data-is-today:items-center in-data-is-today:justify-center in-data-is-today:rounded-full in-data-is-today:bg-scout-purple in-data-is-today:font-semibold in-data-is-today:text-white dark:in-data-is-today:bg-scout-yellow dark:in-data-is-today:text-scout-purple"
                                >
                                    {{ day.dayNumber }}
                                </time>
                            </div>

                            <button
                                v-for="bar in week.bars"
                                :key="`${week.days[0]?.date}-${bar.id}`"
                                type="button"
                                :class="[
                                    'relative z-10 mx-1 min-w-0 truncate px-2 py-0.5 text-left text-xs/6 font-semibold',
                                    eventStyle(bar.event).barClass,
                                ]"
                                :style="{
                                    gridColumn: `${bar.startColumn} / span ${bar.span}`,
                                    gridRow: String(bar.row + 2),
                                }"
                                @click="openEvent(bar.event)"
                            >
                                {{ bar.title }}
                            </button>
                        </div>
                    </div>
                </div>
                <div
                    class="isolate grid w-full grid-cols-7 grid-rows-6 gap-px lg:hidden"
                >
                    <button
                        v-for="day in days"
                        :key="day.date"
                        type="button"
                        :data-is-current-month="
                            day.isCurrentMonth ? '' : undefined
                        "
                        :data-is-selected="
                            day.date === selectedDate ? '' : undefined
                        "
                        :data-is-today="day.isToday ? '' : undefined"
                        class="group relative flex h-14 flex-col px-3 py-2 not-data-is-current-month:bg-gray-50 not-data-is-selected:not-data-is-current-month:not-data-is-today:text-gray-500 hover:bg-gray-100 focus:z-10 data-is-current-month:bg-white not-data-is-selected:data-is-current-month:not-data-is-today:text-gray-900 data-is-current-month:hover:bg-gray-100 data-is-selected:font-semibold data-is-selected:text-white data-is-today:font-semibold not-data-is-selected:data-is-today:text-scout-purple dark:not-data-is-current-month:bg-gray-900 dark:not-data-is-selected:not-data-is-current-month:not-data-is-today:text-gray-400 dark:not-data-is-current-month:before:pointer-events-none dark:not-data-is-current-month:before:absolute dark:not-data-is-current-month:before:inset-0 dark:not-data-is-current-month:before:bg-gray-800/50 dark:hover:bg-gray-900/50 dark:data-is-current-month:bg-gray-900 dark:not-data-is-selected:data-is-current-month:not-data-is-today:text-white dark:data-is-current-month:hover:bg-gray-900/50 dark:not-data-is-selected:data-is-today:text-scout-yellow"
                        @click="selectDay(day.date)"
                    >
                        <time
                            :datetime="day.date"
                            class="ml-auto group-not-data-is-current-month:opacity-75 in-data-is-selected:flex in-data-is-selected:size-6 in-data-is-selected:items-center in-data-is-selected:justify-center in-data-is-selected:rounded-full in-data-is-selected:not-in-data-is-today:bg-scout-purple in-data-is-selected:in-data-is-today:bg-scout-purple dark:in-data-is-selected:not-in-data-is-today:bg-white dark:in-data-is-selected:not-in-data-is-today:text-scout-purple dark:in-data-is-selected:in-data-is-today:bg-scout-yellow dark:in-data-is-selected:in-data-is-today:text-scout-purple"
                        >
                            {{ day.dayNumber }}
                        </time>
                        <span class="sr-only"
                            >{{ day.events.length }} events</span
                        >
                        <span
                            v-if="day.events.length > 0"
                            class="-mx-0.5 mt-auto flex flex-wrap-reverse"
                        >
                            <span
                                v-for="event in day.events"
                                :key="`${day.date}-dot-${event.id}`"
                                :class="[
                                    'mx-0.5 mb-1 size-1.5 rounded-full',
                                    eventStyle(event).dotClass,
                                ]"
                            ></span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
        <div
            class="relative px-4 py-10 sm:px-6 lg:hidden dark:after:pointer-events-none dark:after:absolute dark:after:inset-x-0 dark:after:top-0 dark:after:h-px dark:after:bg-white/10"
        >
            <ol
                class="divide-y divide-gray-100 overflow-hidden rounded-lg bg-white text-sm shadow-sm outline-1 outline-black/5 dark:divide-white/10 dark:bg-gray-800/50 dark:shadow-none dark:-outline-offset-1 dark:outline-white/10"
            >
                <li
                    v-for="event in events"
                    :key="event.id"
                    :class="[
                        'group flex border-l-4 p-4 pr-6 focus-within:bg-gray-50 hover:bg-gray-50 dark:focus-within:bg-white/5 dark:hover:bg-white/5',
                        eventStyle(event).listAccentClass,
                    ]"
                >
                    <div class="flex-auto">
                        <div
                            v-if="event.sections.length"
                            class="mb-2 flex flex-wrap gap-2"
                        >
                            <Badge
                                v-for="section in event.sections"
                                :key="`${event.id}-${section}`"
                                :class="sectionStyle(section).badgeClass"
                            >
                                {{ section }}
                            </Badge>
                        </div>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ event.title }}
                        </p>
                        <time
                            :datetime="event.startsAt"
                            class="mt-2 flex items-center text-gray-700 dark:text-gray-300"
                        >
                            <ClockIcon
                                class="mr-2 size-5 text-gray-400 dark:text-gray-500"
                                aria-hidden="true"
                            />
                            {{ event.listTimeLabel }}
                        </time>
                    </div>
                    <button
                        type="button"
                        class="ml-6 flex-none self-center rounded-md bg-white px-3 py-2 font-semibold text-gray-900 opacity-0 shadow-xs ring-1 ring-gray-300 ring-inset group-hover:opacity-100 hover:ring-gray-400 focus:opacity-100 dark:bg-white/10 dark:text-white dark:shadow-none dark:ring-white/5 dark:hover:bg-white/20 dark:hover:ring-white/5"
                        @click="openEvent(event)"
                    >
                        View<span class="sr-only">, {{ event.title }}</span>
                    </button>
                </li>
                <li
                    v-if="events.length === 0"
                    class="px-4 py-6 text-gray-500 dark:text-gray-400"
                >
                    No events match the selected section filter.
                </li>
            </ol>
        </div>
        <Dialog :open="selectedEvent !== null" @update:open="updateEventDialog">
            <DialogContent class="rounded-none sm:max-w-lg">
                <DialogHeader class="space-y-3">
                    <div
                        v-if="selectedEvent?.sections.length"
                        class="flex flex-wrap items-center gap-3"
                    >
                        <div
                            v-for="section in selectedEvent.sections"
                            :key="section"
                            class="flex items-center"
                        >
                            <component
                                :is="sectionLogo(section) ?? 'div'"
                                v-if="sectionLogo(section)"
                                class="h-7 w-auto bg-none"
                                :class="sectionStyle(section).logoClass"
                            />
                            <span
                                v-else
                                :class="[
                                    'rounded-none px-2 py-1 text-xs font-semibold tracking-wide uppercase',
                                    sectionStyle(section).badgeClass,
                                ]"
                            >
                                {{ section }}
                            </span>
                        </div>
                    </div>
                    <DialogTitle class="text-xl">
                        {{ selectedEvent?.title }}
                    </DialogTitle>
                    <DialogDescription class="space-y-2 text-left">
                        <p>{{ selectedEventDateLabel }}</p>
                        <p class="flex items-center gap-2">
                            <ClockIcon class="size-4" aria-hidden="true" />
                            <span>{{ selectedEvent?.listTimeLabel }}</span>
                        </p>
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
                    <p
                        v-if="selectedEvent?.content"
                        class="leading-6 whitespace-pre-line"
                    >
                        {{ selectedEvent.content }}
                    </p>
                    <p v-else class="text-gray-500 dark:text-gray-400">
                        More details for this event have not been added yet.
                    </p>
                    <Link
                        v-if="selectedEvent"
                        :href="
                            CalendarEventController({
                                event: selectedEvent.slug,
                            })
                        "
                        class="inline-flex bg-scout-purple px-4 py-2 font-bold text-white hover:bg-scout-navy"
                    >
                        Open event page
                    </Link>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
