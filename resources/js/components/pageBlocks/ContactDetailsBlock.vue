<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { ContactDetailsBlockData } from './types';

const props = defineProps<{
    data: ContactDetailsBlockData;
}>();

const primaryIsExternal = computed(() =>
    props.data.primary_url?.startsWith('http'),
);

const secondaryIsExternal = computed(() =>
    props.data.secondary_url?.startsWith('http'),
);

const primaryComponent = computed(() => (primaryIsExternal.value ? 'a' : Link));

const secondaryComponent = computed(() =>
    secondaryIsExternal.value ? 'a' : Link,
);

const cardBackgrounds = [
    'bg-scout-yellow text-scout-purple',
    'bg-scout-blue text-white',
    'bg-scout-green text-scout-navy',
    'bg-scout-red text-white',
];
</script>

<template>
    <section class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-14">
        <div class="grid gap-10 lg:grid-cols-[1.1fr_1.3fr] lg:gap-16">
            <div class="bg-scout-purple px-6 py-8 text-white lg:px-8 lg:py-10">
                <p
                    class="text-sm font-black tracking-[0.24em] text-scout-yellow uppercase"
                >
                    {{ data.eyebrow }}
                </p>
                <h2
                    class="mt-3 text-3xl font-extrabold text-balance lg:text-5xl"
                >
                    {{ data.title }}
                </h2>
                <p class="mt-5 text-lg leading-8 text-white/88">
                    {{ data.intro }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <component
                        :is="primaryComponent"
                        v-if="data.primary_label && data.primary_url"
                        :href="data.primary_url"
                        :target="primaryIsExternal ? '_blank' : undefined"
                        :rel="primaryIsExternal ? 'noreferrer' : undefined"
                        class="inline-flex items-center bg-scout-yellow px-6 py-3 text-base font-black text-scout-purple transition hover:bg-white"
                    >
                        {{ data.primary_label }}
                    </component>

                    <component
                        :is="secondaryComponent"
                        v-if="data.secondary_label && data.secondary_url"
                        :href="data.secondary_url"
                        :target="secondaryIsExternal ? '_blank' : undefined"
                        :rel="secondaryIsExternal ? 'noreferrer' : undefined"
                        class="inline-flex items-center bg-white px-6 py-3 text-base font-black text-scout-purple transition hover:bg-scout-yellow"
                    >
                        {{ data.secondary_label }}
                    </component>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <article
                    v-for="(card, index) in data.cards"
                    :key="`${card.title}-${index}`"
                    :class="[
                        'px-6 py-6',
                        cardBackgrounds[index % cardBackgrounds.length],
                    ]"
                >
                    <h3 class="text-2xl font-extrabold">
                        {{ card.title }}
                    </h3>
                    <p class="mt-3 text-base leading-7 whitespace-pre-line">
                        {{ card.body }}
                    </p>
                </article>
            </div>
        </div>
    </section>
</template>
