<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { show as showPage } from '@/routes/page';
import { Head, usePage } from '@inertiajs/vue3';

// defineProps<{}>()
import BeaversLogo from '@/components/logos/BeaversLogo.vue';
import CubsLogo from '@/components/logos/CubsLogo.vue';
import ExplorersLogo from '@/components/logos/ExplorersLogo.vue';
import NetworkLogo from '@/components/logos/NetworkLogo.vue';
import ScoutsLogo from '@/components/logos/ScoutsLogo.vue';
import SquirrelsLogo from '@/components/logos/SquirrelsLogo.vue';
import HeroSection from '@/pages/HeroSection.vue';
import SectionBlock from '@/pages/SectionBlock.vue';
import type { AppPageProps, GroupProfile } from '@/types';
import type { Component } from 'vue';
import { computed } from 'vue';

type SectionCard = {
    section: string;
    age_range: string;
    time_slot: string;
    description: string;
    page_slug: string;
};

defineProps<{
    sectionCards: SectionCard[];
}>();

const sectionStyles: Record<string, { bgColor: string; textColour: string }> = {
    squirrels: {
        bgColor: 'bg-scout-red',
        textColour: 'text-scout-red',
    },
    beavers: {
        bgColor: 'bg-scout-blue',
        textColour: 'text-scout-blue',
    },
    cubs: {
        bgColor: 'bg-scout-green',
        textColour: 'text-scout-green',
    },
    scouts: {
        bgColor: 'bg-scout-forest-green',
        textColour: 'text-scout-forest-green',
    },
    explorers: {
        bgColor: 'bg-scout-night',
        textColour: 'text-scout-night',
    },
    network: {
        bgColor: 'bg-scout-orange',
        textColour: 'text-scout-orange',
    },
};

const sectionLogos: Record<string, Component> = {
    squirrels: SquirrelsLogo,
    beavers: BeaversLogo,
    cubs: CubsLogo,
    scouts: ScoutsLogo,
    explorers: ExplorersLogo,
    network: NetworkLogo,
};

const page = usePage<AppPageProps>();
const groupProfile = computed((): GroupProfile => page.props.groupProfile);
</script>
<template>
    <AppLayout>
        <Head :title="`Welcome to ${groupProfile.name}`" />
        <HeroSection />
        <div
            class="mx-auto mt-6 max-w-7xl bg-scout-purple px-4 py-4 text-white"
        >
            <h2 class="mb-6 text-3xl font-extrabold lg:text-6xl">
                #Skills For Life
            </h2>
            <div class="prose prose-sm max-w-none text-white lg:prose-lg">
                <p>
                    As Scouts, we believe in providing young people with skills
                    for life. That’s why we encourage our young people to do
                    more, learn more and be more.
                </p>
                <p>
                    Each week, we give over 450,000 young people the opportunity
                    to enjoy fun and adventure while developing the skills they
                    need to succeed. We’re talking about teamwork, leadership
                    and resilience – skills that have helped Scouts become
                    everything from teachers and social workers to astronauts
                    and Olympians.
                </p>
                <p>
                    We believe in bringing people together. We celebrate
                    diversity and stand against intolerance, always. We’re part
                    of a worldwide movement, creating stronger communities and
                    inspiring positive futures.
                </p>
            </div>
        </div>
        <div
            class="mx-auto grid max-w-7xl gap-x-6 gap-y-6 py-6 md:grid-cols-2 lg:grid-cols-3"
        >
            <SectionBlock
                v-for="card in sectionCards"
                :key="card.page_slug"
                :bgColor="
                    sectionStyles[card.page_slug]?.bgColor ??
                    'bg-scout-forest-green'
                "
                :textColour="
                    sectionStyles[card.page_slug]?.textColour ??
                    'text-scout-forest-green'
                "
                :section="card.section"
                :ageRange="card.age_range"
                :timeSlot="card.time_slot"
                :description="card.description"
                :link="showPage({ page: card.page_slug })"
            >
                <template v-slot:logo>
                    <component
                        :is="sectionLogos[card.page_slug] ?? ScoutsLogo"
                        class="mx-auto h-14 w-auto max-w-3/4"
                    />
                </template>
            </SectionBlock>
        </div>
    </AppLayout>
</template>
