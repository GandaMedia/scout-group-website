<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import SocialMeta from '@/components/SocialMeta.vue';
</script>

<template>
    <SocialMeta />
    <AppHeader />
    <slot />
    <AppFooter />
</template>
