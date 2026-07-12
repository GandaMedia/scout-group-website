<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import GroupLogo from '@/components/logos/GroupLogo.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <AppHeader />

        <main class="flex-1 bg-muted/40">
            <section class="bg-scout-purple px-4 py-8 text-white lg:py-10">
                <div
                    class="mx-auto flex max-w-7xl flex-col items-center gap-4 text-center"
                >
                    <GroupLogo class="h-16 text-white" />
                    <div class="max-w-xl space-y-2">
                        <h1 class="text-3xl font-black lg:text-4xl">
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-sm font-medium text-white/90 lg:text-base"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="px-4 py-8 lg:py-12">
                <div
                    class="mx-auto w-full max-w-md overflow-hidden rounded-lg border border-border bg-card shadow-sm"
                >
                    <div class="grid h-2 grid-cols-4">
                        <div class="bg-scout-red" />
                        <div class="bg-scout-blue" />
                        <div class="bg-scout-green" />
                        <div class="bg-scout-forest-green" />
                    </div>
                    <div class="p-6 sm:p-8">
                        <slot />
                    </div>
                </div>
            </section>
        </main>

        <AppFooter />
    </div>
</template>
