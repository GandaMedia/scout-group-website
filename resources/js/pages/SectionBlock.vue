<template>
    <div
        :class="[
            bgColor,
            'flex h-full flex-col items-center px-3 py-6 text-white',
        ]"
    >
        <div class="flex flex-1 flex-col items-center">
            <Link v-if="link" :href="link" class="h-18 cursor-pointer lg:mb-4">
                <slot name="logo"></slot>
            </Link>
            <div v-else class="h-18 lg:mb-4">
                <slot name="logo"></slot>
            </div>
            <h3 class="text-2xl lg:mb-1">
                <span class="sr-only">{{ section }}</span> {{ ageRange }}
            </h3>
            <h4 class="text-lg">{{ timeSlot }}</h4>
            <p class="text-md mt-2 text-center text-pretty italic lg:mt-6">
                {{ description }}
            </p>
        </div>
        <Link
            as="button"
            :href="link"
            v-if="link"
            :class="[
                textColour,
                'group mt-8 inline-flex cursor-pointer items-center gap-2 bg-white px-4 py-2 text-lg font-semibold shadow-sm transition duration-300 ease-in-out hover:bg-white/95 hover:shadow-md motion-safe:hover:-translate-y-1',
            ]"
        >
            <span>Find out more</span>
            <span
                aria-hidden="true"
                class="transition-transform duration-300 ease-in-out motion-safe:group-hover:translate-x-1"
                >→</span
            >
        </Link>
    </div>
</template>
<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

interface SectionBlockProps {
    section: string;
    ageRange: string;
    timeSlot: string;
    description: string;
    bgColor: string;
    textColour: string;
    link: InertiaLinkProps['href'] | null | undefined;
}

withDefaults(defineProps<SectionBlockProps>(), {
    section: '',
    ageRange: '',
    timeSlot: '',
    description: '',
    bgColor: 'bg-scout-purple',
    textColour: 'text-scout-purple',
    link: null,
});
</script>
