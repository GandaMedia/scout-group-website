<script setup lang="ts">
import PageBlockRenderer from '@/components/pageBlocks/PageBlockRenderer.vue';
import type { CmsPage } from '@/components/pageBlocks/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    page: CmsPage;
}>();
</script>

<template>
    <AppLayout>
        <Head :title="page.title" />

        <main
            class="bg-linear-to-b from-white via-scout-pink/10 to-white pb-16"
        >
            <PageBlockRenderer :blocks="page.blocks" :pageSlug="page.slug" />
        </main>
    </AppLayout>
</template>
