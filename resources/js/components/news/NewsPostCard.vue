<script setup lang="ts">
import { show as showNewsPost, tag as showNewsTag } from '@/routes/news';
import type { NewsPostSummary } from '@/types/news';
import { Link } from '@inertiajs/vue3';

defineProps<{
    post: NewsPostSummary;
}>();

const formatDate = (value: string | null): string => {
    if (!value) {
        return 'Draft';
    }

    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};
</script>

<template>
    <article
        class="group flex h-full flex-col overflow-hidden border border-scout-purple/10 bg-white transition duration-300 hover:-translate-y-1 dark:border-white/10 dark:bg-card"
    >
        <Link
            :href="showNewsPost({ post: post.slug })"
            class="block overflow-hidden bg-scout-navy"
        >
            <img
                v-if="post.image"
                :src="post.image"
                :alt="post.title"
                class="h-56 w-full object-cover transition duration-500 group-hover:scale-105"
            />
            <div v-else class="flex h-56 items-end bg-scout-navy p-6">
                <span
                    class="max-w-xs text-2xl font-black tracking-tight text-white"
                >
                    {{ post.title }}
                </span>
            </div>
        </Link>

        <div class="flex flex-1 flex-col p-6">
            <div
                class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm font-semibold text-scout-navy/70 dark:text-muted-foreground"
            >
                <span>{{ formatDate(post.published_at) }}</span>
                <template v-if="post.author_name">
                    <span class="h-1.5 w-1.5 rounded-full bg-scout-red" />
                    <span>{{ post.author_name }}</span>
                </template>
            </div>

            <h2
                class="text-2xl font-black tracking-tight text-scout-navy dark:text-white"
            >
                <Link
                    :href="showNewsPost({ post: post.slug })"
                    class="transition hover:text-scout-purple"
                >
                    {{ post.title }}
                </Link>
            </h2>

            <p
                v-if="post.excerpt"
                class="mt-4 flex-1 text-base leading-7 text-slate-700 dark:text-muted-foreground"
            >
                {{ post.excerpt }}
            </p>
            <p
                v-else-if="post.is_password_protected"
                class="mt-4 flex-1 text-sm font-bold tracking-[0.2em] text-scout-navy/60 uppercase dark:text-muted-foreground"
            >
                Password protected
            </p>

            <div v-if="post.tags.length" class="mt-5 flex flex-wrap gap-2">
                <Link
                    v-for="tagItem in post.tags"
                    :key="tagItem.slug"
                    :href="showNewsTag({ tag: tagItem.slug })"
                    class="border border-scout-purple/15 bg-white px-3 py-1 text-xs font-bold tracking-[0.2em] text-scout-purple uppercase transition hover:border-scout-purple/35 hover:bg-scout-purple/12 dark:border-scout-purple/40 dark:bg-scout-purple/15 dark:text-scout-pink dark:hover:bg-scout-purple/25"
                >
                    {{ tagItem.name }}
                </Link>
            </div>

            <div class="mt-6">
                <Link
                    :href="showNewsPost({ post: post.slug })"
                    class="inline-flex items-center gap-2 text-sm font-black tracking-[0.2em] text-scout-red uppercase transition hover:text-scout-purple"
                >
                    {{
                        post.is_password_protected ? 'Unlock post' : 'Read more'
                    }}
                    <span aria-hidden="true">→</span>
                </Link>
            </div>
        </div>
    </article>
</template>
