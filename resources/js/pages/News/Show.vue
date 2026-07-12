<script setup lang="ts">
import { unlock as unlockPost } from '@/actions/App/Http/Controllers/PostController';
import InputError from '@/components/InputError.vue';
import PageBlockRenderer from '@/components/pageBlocks/PageBlockRenderer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as newsIndex, tag as showNewsTag } from '@/routes/news';
import type { NewsPost } from '@/types/news';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    post: NewsPost;
}>();

const form = useForm({
    password: '',
});

const formatDate = (value: string | null): string => {
    if (!value) {
        return 'Draft';
    }

    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
};

const submit = (): void => {
    form.post(unlockPost({ post: props.post.slug }).url, {
        preserveScroll: true,
        onSuccess: () => form.reset('password'),
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="post.title" />

        <main class="bg-white pb-20">
            <section class="px-4 pt-10 lg:px-0">
                <div class="mx-auto max-w-7xl">
                    <Link
                        :href="newsIndex()"
                        class="inline-flex items-center gap-2 text-sm font-black tracking-[0.25em] text-scout-purple uppercase transition hover:text-scout-red"
                    >
                        <span aria-hidden="true">←</span>
                        Back to news
                    </Link>

                    <div
                        class="mt-6 overflow-hidden border border-scout-purple/10 bg-white"
                    >
                        <div
                            class="bg-scout-navy px-6 py-10 text-white lg:px-12 lg:py-12"
                        >
                            <div
                                class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm font-bold tracking-[0.22em] text-white/75 uppercase"
                            >
                                <span>{{ formatDate(post.published_at) }}</span>
                                <template v-if="post.author_name">
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-scout-yellow"
                                    />
                                    <span>{{ post.author_name }}</span>
                                </template>
                            </div>

                            <h1
                                class="mt-5 max-w-4xl text-4xl font-black tracking-tight lg:text-6xl"
                            >
                                {{ post.title }}
                            </h1>

                            <div
                                v-if="post.tags.length"
                                class="mt-6 flex flex-wrap gap-2"
                            >
                                <Link
                                    v-for="tagItem in post.tags"
                                    :key="tagItem.slug"
                                    :href="showNewsTag({ tag: tagItem.slug })"
                                    class="border border-white/20 bg-scout-purple px-3 py-1 text-xs font-black tracking-[0.2em] text-white uppercase transition hover:bg-white/20"
                                >
                                    {{ tagItem.name }}
                                </Link>
                            </div>
                        </div>

                        <div class="bg-white">
                            <div
                                v-if="
                                    post.is_password_protected &&
                                    !post.is_authorized
                                "
                                class="mx-auto max-w-2xl px-6 py-12 lg:px-12"
                            >
                                <div
                                    class="border border-scout-purple/10 bg-slate-50 p-6"
                                >
                                    <p
                                        class="text-sm font-black tracking-[0.24em] text-scout-red uppercase"
                                    >
                                        Password protected
                                    </p>
                                    <p
                                        class="mt-4 text-base leading-7 text-slate-700"
                                    >
                                        Enter the site-wide post password to
                                        view this update. Access stays active
                                        for one hour and refreshes each time you
                                        open a protected post.
                                    </p>

                                    <form
                                        class="mt-6 space-y-4"
                                        @submit.prevent="submit"
                                    >
                                        <div>
                                            <label
                                                for="post-password"
                                                class="mb-2 block text-sm font-bold tracking-[0.18em] text-scout-navy uppercase"
                                            >
                                                Password
                                            </label>
                                            <input
                                                id="post-password"
                                                v-model="form.password"
                                                type="password"
                                                class="w-full border border-scout-purple/15 bg-white px-4 py-3 text-base text-scout-navy transition outline-none focus:border-scout-red"
                                                autocomplete="current-password"
                                            />
                                            <InputError
                                                :message="form.errors.password"
                                                class="mt-2"
                                            />
                                        </div>

                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center bg-scout-red px-5 py-3 text-sm font-black tracking-[0.2em] text-white uppercase transition hover:bg-scout-navy disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="form.processing"
                                        >
                                            View post
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <PageBlockRenderer v-else :blocks="post.blocks" />
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
