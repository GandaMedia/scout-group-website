<template>
    <div class="px-4 lg:px-0">
        <div
            class="relative z-20 mx-auto mb-2 flex max-w-7xl flex-row flex-wrap items-center justify-between gap-y-4 py-4"
        >
            <Link :href="HomeController()" class="flex items-center gap-x-2">
                <GroupLogo class="h-18 text-scout-purple" />
                <h1 class="sr-only">{{ groupProfile.name }}</h1>
            </Link>
            <MainMenu />
            <div class="order-3 w-full lg:order-none lg:w-auto">
                <div
                    class="relative mx-auto w-3/4 max-w-sm items-center lg:w-full"
                >
                    <form :action="SearchController.url()" method="get">
                        <Input
                            id="search"
                            name="q"
                            type="search"
                            placeholder="Search the site"
                            class="pl-10"
                        />
                        <button
                            type="submit"
                            class="absolute inset-y-0 start-0 flex items-center justify-center px-2"
                            aria-label="Search"
                        >
                            <Search class="size-6 text-muted-foreground" />
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div v-if="sectionBarItems.length" class="lg:bg-scout-purple">
        <div
            class="mx-auto grid max-w-7xl grid-cols-1 text-center lg:grid-cols-[var(--section-grid-columns)]"
            :style="{
                '--section-grid-columns': `repeat(${sectionBarItems.length}, minmax(0, 1fr))`,
            }"
        >
            <div
                v-for="section in sectionBarItems"
                :key="section.slug"
                :class="section.bgClass"
            >
                <Link
                    prefetch
                    :href="section.href"
                    class="flex h-16 w-full items-center justify-center overflow-hidden px-3 py-2 text-white"
                >
                    <component
                        :is="section.logo"
                        :class="[
                            sectionLogoHeightClass,
                            'max-w-full shrink transition-transform duration-200 hover:scale-110',
                        ]"
                    />
                    <h2 class="sr-only">{{ section.name }}</h2>
                </Link>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import HomeController from '@/actions/App/Http/Controllers/HomeController';
import SearchController from '@/actions/App/Http/Controllers/SearchController';
import BeaversLogo from '@/components/logos/BeaversLogo.vue';
import CubsLogo from '@/components/logos/CubsLogo.vue';
import ExplorersLogo from '@/components/logos/ExplorersLogo.vue';
import GroupLogo from '@/components/logos/GroupLogo.vue';
import NetworkLogo from '@/components/logos/NetworkLogo.vue';
import ScoutsLogo from '@/components/logos/ScoutsLogo.vue';
import SquirrelsLogo from '@/components/logos/SquirrelsLogo.vue';
import MainMenu from '@/components/MainMenu.vue';
import { Input } from '@/components/ui/input';
import type { AppPageProps, GroupProfile } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed } from 'vue';

const page = usePage<AppPageProps>();
const groupProfile = computed((): GroupProfile => page.props.groupProfile);

type MenuItem = {
    id: number | string;
    name: string;
    link: string | null;
    external: boolean;
    children: MenuItem[];
};

type SectionBarItem = {
    name: string;
    slug: string;
    href: string;
    bgClass: string;
    logo: Component;
};

const sectionPresentation: Record<
    string,
    { bgClass: string; logo: Component }
> = {
    squirrels: {
        bgClass: 'bg-scout-red',
        logo: SquirrelsLogo,
    },
    beavers: {
        bgClass: 'bg-scout-blue',
        logo: BeaversLogo,
    },
    cubs: {
        bgClass: 'bg-scout-green',
        logo: CubsLogo,
    },
    scouts: {
        bgClass: 'bg-scout-forest-green',
        logo: ScoutsLogo,
    },
    explorers: {
        bgClass: 'bg-scout-night',
        logo: ExplorersLogo,
    },
    network: {
        bgClass: 'bg-scout-orange',
        logo: NetworkLogo,
    },
};

const menu = computed((): MenuItem[] => (page.props.menu as MenuItem[]) ?? []);

const sectionBarItems = computed((): SectionBarItem[] => {
    const sectionsMenu = menu.value.find((item) => item.name === 'Sections');

    if (!sectionsMenu) {
        return [];
    }

    return sectionsMenu.children
        .map((item): SectionBarItem | null => {
            const href = item.link;

            if (!href) {
                return null;
            }

            const slug = href.split('?')[0]?.split('/').filter(Boolean).pop();

            if (!slug || !sectionPresentation[slug]) {
                return null;
            }

            return {
                name: item.name,
                slug,
                href,
                ...sectionPresentation[slug],
            };
        })
        .filter((item): item is SectionBarItem => item !== null);
});

const sectionLogoHeightClass = computed((): string => {
    if (sectionBarItems.value.length <= 4) {
        return 'h-8';
    }

    if (sectionBarItems.value.length === 5) {
        return 'h-7';
    }

    return 'h-6';
});
</script>
