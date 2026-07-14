<script setup lang="ts">
import { computed } from 'vue';
import type { ImageTextBlockData } from './types';

const props = defineProps<{
    data: ImageTextBlockData;
}>();

const imageIsRight = computed(() => props.data.image_position === 'right');

const gridClass = computed(() => {
    switch (props.data.image_width) {
        case 'one-third':
            return 'lg:grid-cols-[2fr_1fr]';
        case 'two-thirds':
            return 'lg:grid-cols-[1fr_2fr]';
        default:
            return 'lg:grid-cols-2';
    }
});
</script>

<template>
    <section class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-14">
        <div
            :class="[
                'grid items-center gap-8 overflow-hidden bg-white lg:gap-12 dark:bg-card',
                gridClass,
            ]"
        >
            <div
                :class="[
                    'px-6 py-8 lg:px-10 lg:py-12',
                    imageIsRight ? 'lg:order-1' : 'lg:order-2',
                ]"
            >
                <p
                    class="text-sm font-black tracking-[0.22em] text-scout-green uppercase"
                >
                    Scout Stories
                </p>
                <h2
                    class="mt-3 text-3xl font-extrabold text-scout-navy lg:text-4xl dark:text-white"
                >
                    {{ data.title }}
                </h2>
                <div
                    class="prose mt-5 max-w-none dark:prose-invert prose-a:text-scout-purple dark:prose-a:text-scout-pink prose-strong:text-scout-navy dark:prose-strong:text-white"
                    v-html="data.content"
                />
            </div>

            <div
                :class="[
                    'relative min-h-72 overflow-hidden bg-scout-purple/8 dark:bg-scout-purple/20',
                    imageIsRight ? 'lg:order-2' : 'lg:order-1',
                ]"
            >
                <img
                    v-if="data.image"
                    :src="data.image"
                    :alt="data.title"
                    class="h-full min-h-72 w-full object-cover"
                />
                <div
                    v-else
                    class="flex min-h-72 items-center justify-center bg-scout-blue text-sm font-black tracking-[0.2em] text-white uppercase"
                >
                    Scout adventure
                </div>
            </div>
        </div>
    </section>
</template>
