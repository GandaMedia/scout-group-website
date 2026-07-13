<script setup lang="ts">
import BeaversLogo from '@/components/logos/BeaversLogo.vue';
import CubsLogo from '@/components/logos/CubsLogo.vue';
import ExplorersLogo from '@/components/logos/ExplorersLogo.vue';
import NetworkLogo from '@/components/logos/NetworkLogo.vue';
import ScoutsLogo from '@/components/logos/ScoutsLogo.vue';
import SquirrelsLogo from '@/components/logos/SquirrelsLogo.vue';
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed } from 'vue';
import type { HeroBlockData } from './types';

const props = defineProps<{
    data: HeroBlockData;
    pageSlug?: string;
}>();

type SectionHeroStyle = {
    name: string;
    logo: Component;
    sectionClass: string;
    overlayClass: string;
    buttonClass: string;
};

const sectionHeroStyles: Record<string, SectionHeroStyle> = {
    contact: {
        name: 'Contact',
        logo: ScoutsLogo,
        sectionClass: 'bg-scout-purple',
        overlayClass: 'bg-scout-purple/85',
        buttonClass:
            'bg-scout-purple text-white hover:bg-white hover:text-scout-purple',
    },
    squirrels: {
        name: 'Squirrels',
        logo: SquirrelsLogo,
        sectionClass: 'bg-scout-red',
        overlayClass: 'bg-scout-red/85',
        buttonClass:
            'bg-scout-red text-white hover:bg-white hover:text-scout-red',
    },
    beavers: {
        name: 'Beavers',
        logo: BeaversLogo,
        sectionClass: 'bg-scout-blue',
        overlayClass: 'bg-scout-blue/85',
        buttonClass:
            'bg-scout-blue text-white hover:bg-white hover:text-scout-blue',
    },
    cubs: {
        name: 'Cubs',
        logo: CubsLogo,
        sectionClass: 'bg-scout-green',
        overlayClass: 'bg-scout-green/85',
        buttonClass:
            'bg-scout-green text-white hover:bg-white hover:text-scout-green',
    },
    scouts: {
        name: 'Scouts',
        logo: ScoutsLogo,
        sectionClass: 'bg-scout-forest-green',
        overlayClass: 'bg-scout-forest-green/85',
        buttonClass:
            'bg-scout-forest-green text-white hover:bg-white hover:text-scout-forest-green',
    },
    explorers: {
        name: 'Explorers',
        logo: ExplorersLogo,
        sectionClass: 'bg-scout-night',
        overlayClass: 'bg-scout-night/85',
        buttonClass:
            'bg-scout-night text-white hover:bg-white hover:text-scout-night',
    },
    network: {
        name: 'Network',
        logo: NetworkLogo,
        sectionClass: 'bg-scout-orange',
        overlayClass: 'bg-scout-orange/85',
        buttonClass:
            'bg-scout-orange text-white hover:bg-white hover:text-scout-orange',
    },
};

const sectionSlug = computed((): string | null => {
    const slug = props.pageSlug?.toLowerCase();

    if (slug && slug in sectionHeroStyles) {
        return slug;
    }

    const eyebrow = props.data.eyebrow?.toLowerCase() ?? '';

    return (
        Object.keys(sectionHeroStyles).find((section) =>
            eyebrow.startsWith(section),
        ) ?? null
    );
});

const sectionHeroStyle = computed(() =>
    sectionSlug.value ? sectionHeroStyles[sectionSlug.value] : null,
);

const eyebrowSuffix = computed((): string | null => {
    const style = sectionHeroStyle.value;
    const eyebrow = props.data.eyebrow?.trim();

    if (!style || !eyebrow) {
        return eyebrow ?? null;
    }

    return eyebrow.replace(new RegExp(`^${style.name}\\s*`, 'i'), '').trim();
});

const linkIsExternal = computed(() =>
    props.data.primary_url?.startsWith('http'),
);

const linkComponent = computed(() => (linkIsExternal.value ? 'a' : Link));
</script>

<template>
    <section
        :class="[
            sectionHeroStyle?.sectionClass ?? 'bg-scout-forest-green',
            'relative overflow-hidden text-white',
        ]"
    >
        <div
            v-if="data.image"
            class="absolute inset-0 bg-cover bg-center opacity-30"
            :style="{ backgroundImage: `url(${data.image})` }"
        />
        <div
            :class="[
                sectionHeroStyle?.overlayClass ?? 'bg-scout-forest-green/85',
                'absolute inset-0',
            ]"
        />

        <div
            class="relative mx-auto flex min-h-[26rem] max-w-7xl items-end px-4 py-10 lg:px-8 lg:py-14"
        >
            <div class="max-w-3xl">
                <p
                    v-if="data.eyebrow"
                    class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-black tracking-[0.26em] text-white uppercase"
                >
                    <component
                        :is="sectionHeroStyle.logo"
                        v-if="sectionHeroStyle"
                        class="h-8 w-auto max-w-56"
                    />
                    <span v-if="sectionHeroStyle" class="sr-only">
                        {{ sectionHeroStyle.name }}
                    </span>
                    <span v-if="eyebrowSuffix">{{ eyebrowSuffix }}</span>
                </p>
                <h1
                    class="mt-4 text-4xl font-extrabold text-balance lg:text-6xl"
                >
                    {{ data.title }}
                </h1>
                <p
                    class="mt-5 max-w-2xl text-lg leading-8 text-white/88 lg:text-xl"
                >
                    {{ data.body }}
                </p>

                <component
                    :is="linkComponent"
                    v-if="data.primary_label && data.primary_url"
                    :href="data.primary_url"
                    :target="linkIsExternal ? '_blank' : undefined"
                    :rel="linkIsExternal ? 'noreferrer' : undefined"
                    :class="[
                        sectionHeroStyle?.buttonClass ??
                            'bg-scout-yellow text-scout-purple hover:bg-white',
                        'mt-8 inline-flex items-center border-2 border-white px-6 py-3 text-base font-black transition',
                    ]"
                >
                    {{ data.primary_label }}
                </component>
            </div>
        </div>
    </section>
</template>
