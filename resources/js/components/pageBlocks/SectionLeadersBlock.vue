<script setup lang="ts">
import type { SectionLeadersBlockData } from './types';

defineProps<{
    data: SectionLeadersBlockData;
}>();
</script>

<template>
    <section
        v-if="data.leaders.length"
        class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-14"
    >
        <div
            class="overflow-hidden bg-scout-navy text-white shadow-[0_24px_90px_-54px_rgba(0,57,130,0.85)]"
        >
            <div
                class="grid gap-8 px-6 py-8 lg:grid-cols-[0.75fr_1.25fr] lg:px-10 lg:py-12"
            >
                <div>
                    <p
                        class="text-sm font-black tracking-[0.24em] text-scout-yellow uppercase"
                    >
                        {{ data.eyebrow }}
                    </p>
                    <h2
                        class="mt-3 text-3xl font-extrabold text-balance lg:text-5xl"
                    >
                        {{ data.title }}
                    </h2>
                    <p class="mt-5 text-lg leading-8 text-white/86">
                        {{ data.intro }}
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <article
                        v-for="leader in data.leaders"
                        :key="`${leader.name}-${leader.scout_name ?? 'leader'}`"
                        class="group overflow-hidden bg-white text-scout-navy dark:bg-card dark:text-white"
                    >
                        <div
                            class="relative aspect-square overflow-hidden bg-scout-blue/12"
                        >
                            <img
                                v-if="leader.photo"
                                :src="leader.photo"
                                :alt="`Photo of ${leader.name}`"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-full items-center justify-center bg-scout-blue text-sm font-black tracking-[0.2em] text-white uppercase"
                            >
                                Leader
                            </div>
                        </div>

                        <div class="px-5 py-5">
                            <p
                                v-if="leader.scout_name"
                                class="text-xs font-black tracking-[0.22em] text-scout-purple uppercase"
                            >
                                {{ leader.scout_name }}
                            </p>
                            <h3 class="mt-1 text-2xl font-extrabold">
                                {{ leader.name }}
                            </h3>
                            <div
                                class="prose prose-sm mt-4 max-w-none dark:prose-invert prose-p:leading-7 prose-strong:text-scout-navy dark:prose-strong:text-white"
                                v-html="leader.bio"
                            />
                            <p
                                v-if="leader.fun_fact"
                                class="mt-5 border-l-4 border-scout-yellow bg-scout-yellow/28 px-4 py-3 text-sm leading-6 font-bold text-scout-purple"
                            >
                                Fun fact: {{ leader.fun_fact }}
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
