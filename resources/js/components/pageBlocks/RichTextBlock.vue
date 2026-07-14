<script setup lang="ts">
import type { RichTextBlockData } from './types';

defineProps<{
    data: RichTextBlockData;
}>();
</script>

<template>
    <section class="mx-auto max-w-4xl px-4 py-10 lg:px-8 lg:py-14">
        <div
            class="prose prose-lg max-w-none dark:prose-invert prose-headings:font-extrabold prose-a:text-scout-purple dark:prose-a:text-scout-pink prose-strong:text-scout-navy dark:prose-strong:text-white"
            v-html="data.content"
        />
    </section>
</template>
