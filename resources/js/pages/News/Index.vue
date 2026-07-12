<script setup lang="ts">
import NewsPostCard from '@/components/news/NewsPostCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as newsIndex } from '@/routes/news';
import type { AppPageProps, GroupProfile } from '@/types';
import type { NewsArchiveTag, NewsPostSummary } from '@/types/news';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    heading: string;
    description: string;
    tag: NewsArchiveTag | null;
    posts: NewsPostSummary[];
}>();

const page = usePage<AppPageProps>();
const groupProfile = computed((): GroupProfile => page.props.groupProfile);
</script>

<template>
    <AppLayout>
        <Head :title="heading" />

        <main class="min-h-screen bg-white pb-20">
            <section class="px-4 pt-10 lg:px-0">
                <div
                    class="mx-auto max-w-7xl overflow-hidden bg-scout-navy text-white"
                >
                    <div
                        class="grid gap-8 bg-scout-navy px-6 py-10 lg:grid-cols-[1.3fr_0.7fr] lg:px-12 lg:py-14"
                    >
                        <div>
                            <p
                                class="text-sm font-black tracking-[0.35em] text-scout-yellow uppercase"
                            >
                                {{ groupProfile.shortName }}
                            </p>
                            <h1
                                class="mt-4 max-w-3xl text-4xl font-black tracking-tight text-white lg:text-6xl"
                            >
                                {{ heading }}
                            </h1>
                            <p
                                class="mt-6 max-w-2xl text-lg leading-8 text-white/80"
                            >
                                {{ description }}
                            </p>
                        </div>

                        <div
                            class="flex flex-col justify-between border border-white/15 bg-scout-purple p-6"
                        >
                            <div>
                                <p
                                    class="text-sm font-black tracking-[0.3em] text-scout-pink uppercase"
                                >
                                    Archive
                                </p>
                                <p class="mt-3 text-3xl font-black">
                                    {{ posts.length }}
                                </p>
                                <p class="mt-2 text-sm leading-6 text-white/70">
                                    {{
                                        posts.length === 1 ? 'story' : 'stories'
                                    }}
                                    {{ tag ? 'in this tag' : 'ready to read' }}
                                </p>
                            </div>

                            <Link
                                v-if="tag"
                                :href="newsIndex()"
                                class="mt-6 inline-flex items-center gap-2 self-start rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20"
                            >
                                View all news
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-4 pt-10 lg:px-0">
                <div class="mx-auto max-w-7xl">
                    <div
                        v-if="posts.length"
                        class="grid gap-6 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <NewsPostCard
                            v-for="post in posts"
                            :key="post.slug"
                            :post="post"
                        />
                    </div>

                    <div
                        v-else
                        class="border border-dashed border-scout-purple/25 bg-white px-8 py-16 text-center"
                    >
                        <p
                            class="text-sm font-black tracking-[0.35em] text-scout-purple uppercase"
                        >
                            Nothing here yet
                        </p>
                        <h2
                            class="mt-4 text-3xl font-black tracking-tight text-scout-navy"
                        >
                            No published posts yet - coming soon....
                        </h2>
                        <p class="mx-auto mt-4 max-w-2xl text-slate-600">
                            New updates will appear here once they are
                            published.
                        </p>
                        <Link
                            :href="newsIndex()"
                            class="mt-8 inline-flex items-center gap-2 bg-scout-purple px-5 py-3 text-sm font-black tracking-[0.2em] text-white uppercase transition hover:bg-scout-navy"
                        >
                            Back to all news
                        </Link>
                    </div>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
