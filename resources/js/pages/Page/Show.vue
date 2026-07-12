<script setup lang="ts">
import PageBlockRenderer from '@/components/pageBlocks/PageBlockRenderer.vue';
import type { CmsPage } from '@/components/pageBlocks/types';
import SocialMeta from '@/components/SocialMeta.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps<{
    page: CmsPage;
}>();

const pageImage = computed((): string | null => {
    for (const block of props.page.blocks) {
        const image = (block.data as Record<string, unknown>).image;

        if (typeof image === 'string' && image !== '') {
            return image;
        }
    }

    return null;
});
</script>

<template>
    <AppLayout>
        <SocialMeta :title="page.title" :image="pageImage" />

        <main
            class="bg-linear-to-b from-white via-scout-pink/10 to-white pb-16"
        >
            <PageBlockRenderer :blocks="page.blocks" :pageSlug="page.slug" />
        </main>
    </AppLayout>
</template>
