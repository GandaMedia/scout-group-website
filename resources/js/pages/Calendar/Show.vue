<script setup lang="ts">
import SocialMeta from '@/components/SocialMeta.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CalendarMonthView from '@/pages/CalendarMonthView.vue';

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

defineProps<{
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
</script>

<template>
    <AppLayout>
        <SocialMeta
            :title="`Calendar | ${month.label}`"
            :description="`Scout meetings, activities and events for ${month.label}.`"
        />
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-0">
            <CalendarMonthView :month="month" :days="days" :filters="filters" />
        </div>
    </AppLayout>
</template>
