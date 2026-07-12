<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { CtaBlockData } from './types';

const props = defineProps<{
    data: CtaBlockData;
}>();

const linkIsExternal = computed(() => props.data.button_url.startsWith('http'));
const linkComponent = computed(() => (linkIsExternal.value ? 'a' : Link));
</script>

<template>
    <section class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-14">
        <div
            class="overflow-hidden bg-scout-red text-white shadow-[0_24px_80px_-48px_rgba(237,63,35,0.8)]"
        >
            <div
                class="grid gap-8 px-6 py-8 lg:grid-cols-[2fr_1fr] lg:px-10 lg:py-12"
            >
                <div>
                    <p
                        class="text-sm font-black tracking-[0.22em] text-scout-yellow uppercase"
                    >
                        Take the next step
                    </p>
                    <h2 class="mt-3 text-3xl font-extrabold lg:text-4xl">
                        {{ data.title }}
                    </h2>
                    <p class="mt-4 max-w-2xl text-lg leading-8 text-white/90">
                        {{ data.body }}
                    </p>
                </div>

                <div class="flex items-center lg:justify-end">
                    <component
                        :is="linkComponent"
                        :href="data.button_url"
                        :target="linkIsExternal ? '_blank' : undefined"
                        :rel="linkIsExternal ? 'noreferrer' : undefined"
                        class="inline-flex items-center bg-white px-6 py-3 text-base font-black text-scout-red transition hover:bg-scout-yellow"
                    >
                        {{ data.button_label }}
                    </component>
                </div>
            </div>
        </div>
    </section>
</template>
