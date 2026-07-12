<script setup lang="ts">
import type { AppPageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        image?: string | null;
        type?: 'website' | 'article';
        publishedAt?: string | null;
        noIndex?: boolean;
    }>(),
    {
        title: undefined,
        description: undefined,
        image: null,
        type: 'website',
        publishedAt: null,
        noIndex: false,
    },
);

const page = usePage<AppPageProps>();
const groupProfile = computed(() => page.props.groupProfile);

const socialTitle = computed(() =>
    props.title
        ? `${props.title} | ${groupProfile.value.name}`
        : groupProfile.value.name,
);

const socialDescription = computed(
    () =>
        props.description ??
        `Skills for life, news, activities and joining information from ${groupProfile.value.name}.`,
);

const canonicalUrl = computed(() => absoluteUrl(page.url));
const socialImage = computed(() =>
    absoluteUrl(props.image ?? '/img/cubs-in-helmets-outdoors-jpg.jpg'),
);

function absoluteUrl(value: string): string {
    const baseUrl = groupProfile.value.websiteUrl || windowLocation();

    try {
        return new URL(value, baseUrl).toString();
    } catch {
        return value;
    }
}

function windowLocation(): string {
    if (typeof window !== 'undefined') {
        return window.location.origin;
    }

    return 'http://localhost';
}
</script>

<template>
    <Head>
        <title>{{ socialTitle }}</title>
        <meta
            head-key="description"
            name="description"
            :content="socialDescription"
        />
        <meta
            v-if="noIndex"
            head-key="robots"
            name="robots"
            content="noindex, nofollow"
        />
        <link head-key="canonical" rel="canonical" :href="canonicalUrl" />

        <meta
            head-key="og:site_name"
            property="og:site_name"
            :content="groupProfile.name"
        />
        <meta head-key="og:locale" property="og:locale" content="en_GB" />
        <meta head-key="og:type" property="og:type" :content="type" />
        <meta head-key="og:title" property="og:title" :content="socialTitle" />
        <meta
            head-key="og:description"
            property="og:description"
            :content="socialDescription"
        />
        <meta head-key="og:url" property="og:url" :content="canonicalUrl" />
        <meta head-key="og:image" property="og:image" :content="socialImage" />
        <meta
            head-key="og:image:alt"
            property="og:image:alt"
            :content="`${groupProfile.name} Scouts`"
        />
        <meta
            v-if="type === 'article' && publishedAt"
            head-key="article:published_time"
            property="article:published_time"
            :content="publishedAt"
        />

        <meta
            head-key="twitter:card"
            name="twitter:card"
            content="summary_large_image"
        />
        <meta
            head-key="twitter:title"
            name="twitter:title"
            :content="socialTitle"
        />
        <meta
            head-key="twitter:description"
            name="twitter:description"
            :content="socialDescription"
        />
        <meta
            head-key="twitter:image"
            name="twitter:image"
            :content="socialImage"
        />
        <meta
            head-key="twitter:image:alt"
            name="twitter:image:alt"
            :content="`${groupProfile.name} Scouts`"
        />
    </Head>
</template>
