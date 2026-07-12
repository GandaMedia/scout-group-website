<script setup lang="ts">
import GroupLogo from '@/components/logos/GroupLogo.vue';
import { home } from '@/routes';
import type { AppPageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Home } from 'lucide-vue-next';
import { computed } from 'vue';

type ErrorContent = {
    eyebrow: string;
    title: string;
    description: string;
};

const props = defineProps<{
    status: number;
}>();

const page = usePage<AppPageProps>();

const errors: Record<number, ErrorContent> = {
    403: {
        eyebrow: 'Permission needed',
        title: 'This area is not open to you',
        description:
            'You may need to sign in with a different account or ask an administrator for access.',
    },
    404: {
        eyebrow: 'Wrong turn',
        title: 'We could not find that page',
        description:
            'The page may have moved, the link may be out of date, or the address may have been typed incorrectly.',
    },
    500: {
        eyebrow: 'Something went wrong',
        title: 'We have hit an unexpected problem',
        description:
            'Please try again in a moment. If the problem continues, let the group team know what you were trying to do.',
    },
    503: {
        eyebrow: 'Back shortly',
        title: 'The site is taking a short break',
        description:
            'We are carrying out maintenance or handling a temporary interruption. Please check back soon.',
    },
};

const content = computed<ErrorContent>(() =>
    props.status in errors
        ? errors[props.status]
        : {
              eyebrow: 'Something went wrong',
              title: 'We could not complete that request',
              description:
                  'Please return to the homepage and try again in a moment.',
          },
);

const groupName = computed(() => page.props.groupProfile.name);

function goBack(): void {
    window.history.back();
}
</script>

<template>
    <Head :title="`${status} - ${content.title}`" />

    <main class="min-h-screen bg-white text-scout-night">
        <div
            class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(22rem,0.7fr)]"
        >
            <section
                class="flex items-center px-6 py-12 sm:px-10 lg:px-16 lg:py-16 xl:px-24"
            >
                <div class="w-full max-w-3xl">
                    <Link
                        :href="home()"
                        class="inline-flex text-scout-purple transition-colors hover:text-scout-night focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-scout-purple"
                        :aria-label="`Go to the ${groupName} homepage`"
                    >
                        <GroupLogo class="h-28 w-auto sm:h-32" />
                    </Link>

                    <div class="mt-12 flex items-center gap-4">
                        <span
                            class="h-1 w-12 bg-scout-teal"
                            aria-hidden="true"
                        />
                        <p class="text-sm font-bold tracking-normal uppercase">
                            {{ content.eyebrow }}
                        </p>
                    </div>

                    <h1
                        class="mt-5 max-w-2xl text-4xl leading-tight font-extrabold sm:text-5xl lg:text-6xl"
                    >
                        {{ content.title }}
                    </h1>

                    <p
                        class="mt-6 max-w-xl text-lg leading-8 text-scout-night/75"
                    >
                        {{ content.description }}
                    </p>

                    <div class="mt-9 flex flex-wrap gap-3">
                        <Link
                            :href="home()"
                            class="inline-flex min-h-12 items-center gap-2 bg-scout-purple px-5 py-3 font-bold text-white transition-colors hover:bg-scout-night focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-scout-purple"
                        >
                            <Home class="size-5" aria-hidden="true" />
                            Go to homepage
                        </Link>
                        <button
                            type="button"
                            class="inline-flex min-h-12 items-center gap-2 border-2 border-scout-night px-5 py-3 font-bold transition-colors hover:bg-scout-night hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-scout-night"
                            @click="goBack"
                        >
                            <ArrowLeft class="size-5" aria-hidden="true" />
                            Go back
                        </button>
                    </div>
                </div>
            </section>

            <aside
                class="relative flex min-h-72 items-center justify-center overflow-hidden bg-scout-night px-6 py-12 text-white lg:min-h-screen"
                aria-hidden="true"
            >
                <div class="absolute inset-x-0 top-0 h-3 bg-scout-yellow" />
                <div
                    class="absolute right-0 bottom-0 h-24 w-2/3 bg-scout-teal"
                />
                <p
                    class="relative text-[8rem] leading-none font-extrabold text-white sm:text-[11rem] lg:text-[13rem]"
                >
                    {{ status }}
                </p>
            </aside>
        </div>
    </main>
</template>
