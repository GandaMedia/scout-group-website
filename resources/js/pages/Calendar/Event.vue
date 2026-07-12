<script setup lang="ts">
import SocialMeta from '@/components/SocialMeta.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { calendar } from '@/routes';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    event: {
        title: string;
        slug: string;
        startsAt: string;
        endsAt: string;
        allDay: boolean;
        content: string | null;
        sections: string[];
        image: string | null;
    };
}>();

const dateLabel = new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'full',
    timeStyle: props.event.allDay ? undefined : 'short',
}).format(new Date(props.event.startsAt));
</script>

<template>
    <AppLayout>
        <SocialMeta
            :title="event.title"
            :description="event.content ?? `${event.title} on ${dateLabel}.`"
            :image="event.image"
            type="article"
        />
        <main class="mx-auto min-h-[55vh] max-w-5xl px-4 py-12 lg:px-0">
            <Link
                :href="calendar()"
                class="text-sm font-black text-scout-purple uppercase"
                >Back to calendar</Link
            >
            <img
                v-if="event.image"
                :src="event.image"
                :alt="event.title"
                class="mt-6 aspect-[16/7] w-full object-cover"
            />
            <div class="mt-8 flex flex-wrap gap-2">
                <span
                    v-for="section in event.sections"
                    :key="section"
                    class="bg-scout-purple px-3 py-1 text-xs font-bold text-white uppercase"
                    >{{ section }}</span
                >
            </div>
            <h1 class="mt-4 text-4xl font-black text-scout-navy lg:text-6xl">
                {{ event.title }}
            </h1>
            <p class="mt-5 text-lg font-bold text-scout-purple">
                {{ dateLabel }}
            </p>
            <p
                v-if="event.content"
                class="mt-8 text-lg leading-8 whitespace-pre-line text-slate-700"
            >
                {{ event.content }}
            </p>
            <p v-else class="mt-8 text-slate-600">
                More details for this event have not been added yet.
            </p>
        </main>
    </AppLayout>
</template>
