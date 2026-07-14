<script setup lang="ts">
import SearchController from '@/actions/App/Http/Controllers/SearchController';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';

defineProps<{
    query: string;
    results: Array<{
        type: 'Page' | 'News';
        title: string;
        href: string;
        publishedAt?: string | null;
    }>;
}>();
</script>

<template>
    <AppLayout>
        <Head title="Search" />
        <main
            class="mx-auto min-h-[55vh] max-w-5xl px-4 py-12 text-foreground lg:px-0"
        >
            <p class="text-sm font-black text-scout-purple uppercase">Search</p>
            <h1
                class="mt-2 text-4xl font-black text-scout-navy dark:text-white"
            >
                Find something
            </h1>
            <form
                :action="SearchController.url()"
                method="get"
                class="mt-8 flex max-w-2xl border border-slate-300 bg-white focus-within:border-scout-purple dark:border-white/10 dark:bg-card"
            >
                <Search
                    class="mt-3.5 ml-4 size-5 shrink-0 text-slate-400 dark:text-muted-foreground"
                />
                <input
                    name="q"
                    type="search"
                    :value="query"
                    minlength="2"
                    maxlength="100"
                    class="min-w-0 flex-1 bg-transparent px-3 py-3 outline-none"
                    placeholder="Search pages and news"
                    autofocus
                />
                <button
                    type="submit"
                    class="bg-scout-purple px-5 py-3 font-bold text-white hover:bg-scout-navy"
                >
                    Search
                </button>
            </form>

            <p
                v-if="query.length === 1"
                class="mt-6 text-slate-600 dark:text-muted-foreground"
            >
                Enter at least two characters.
            </p>
            <div v-else-if="query.length >= 2" class="mt-10">
                <p class="text-sm text-slate-600 dark:text-muted-foreground">
                    {{ results.length }}
                    {{ results.length === 1 ? 'result' : 'results' }} for “{{
                        query
                    }}”
                </p>
                <ul
                    v-if="results.length"
                    class="mt-4 divide-y divide-slate-200 border-y border-slate-200 dark:divide-white/10 dark:border-white/10"
                >
                    <li
                        v-for="result in results"
                        :key="`${result.type}-${result.href}`"
                    >
                        <Link
                            :href="result.href"
                            class="block px-2 py-5 hover:bg-slate-50 dark:hover:bg-white/5"
                        >
                            <span
                                class="text-xs font-black text-scout-purple uppercase"
                                >{{ result.type }}</span
                            >
                            <h2
                                class="mt-1 text-xl font-black text-scout-navy dark:text-white"
                            >
                                {{ result.title }}
                            </h2>
                        </Link>
                    </li>
                </ul>
                <p
                    v-else
                    class="mt-6 border-l-4 border-scout-yellow bg-yellow-50 p-5 text-slate-700 dark:bg-scout-yellow/10 dark:text-white"
                >
                    No published pages or news matched that search.
                </p>
            </div>
        </main>
    </AppLayout>
</template>
