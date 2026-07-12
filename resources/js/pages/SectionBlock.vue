<template>
    <div
        :class="[
            bgColor,
            'group flex flex-col items-center px-3 py-6 text-white',
        ]"
    >
        <div class="h-18 lg:mb-4">
            <slot name="logo"></slot>
        </div>
        <h3 class="text-2xl lg:mb-1">
            <span class="sr-only">{{ section }}</span> {{ ageRange }}
        </h3>
        <h4 class="text-lg">{{ timeSlot }}</h4>
        <p class="text-md mt-2 text-center text-pretty italic lg:mt-6">
            {{ description }}
        </p>
        <Link
            as="button"
            :href="link"
            v-if="link"
            :class="[
                textColour,
                'group-hover:' + textColour,
                'mt-8 bg-white px-4 py-2 text-lg font-semibold group-hover:bg-white group-hover:text-2xl group-hover:font-bold hover:bg-white hover:shadow-lg',
            ]"
            >Find out more
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
