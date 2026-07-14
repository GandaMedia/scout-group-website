<template>
    <div
        class="w-full bg-scout-forest-green bg-cover bg-center"
        style="background-image: url('/img/cubs-in-helmets-outdoors-jpg.jpg')"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-start">
            <div
                class="flex h-64 w-1/2 items-center justify-center bg-white/75 text-white lg:h-120 lg:w-1/3 dark:bg-scout-night/80"
            >
                <GroupLogo
                    stacked
                    class="h-full py-12 text-scout-purple dark:text-white"
                />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import GroupLogo from '@/components/logos/GroupLogo.vue';
</script>
