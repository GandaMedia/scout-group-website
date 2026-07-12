<script setup lang="ts">
import ContactDetailsBlock from '@/components/pageBlocks/ContactDetailsBlock.vue';
import ContactFormBlock from '@/components/pageBlocks/ContactFormBlock.vue';
import CtaBlock from '@/components/pageBlocks/CtaBlock.vue';
import GoogleMapBlock from '@/components/pageBlocks/GoogleMapBlock.vue';
import HeroBlock from '@/components/pageBlocks/HeroBlock.vue';
import ImageTextBlock from '@/components/pageBlocks/ImageTextBlock.vue';
import RichTextBlock from '@/components/pageBlocks/RichTextBlock.vue';
import SectionLeadersBlock from '@/components/pageBlocks/SectionLeadersBlock.vue';
import type { PageBlock } from '@/components/pageBlocks/types';

const props = defineProps<{
    blocks: PageBlock[];
    pageSlug?: string;
}>();

const components = {
    ContactDetailsBlock,
    ContactFormBlock,
    CtaBlock,
    GoogleMapBlock,
    HeroBlock,
    ImageTextBlock,
    RichTextBlock,
    SectionLeadersBlock,
};

function propsFor(block: PageBlock): {
    data: PageBlock['data'];
    pageSlug?: string;
} {
    return block.type === 'HeroBlock'
        ? { data: block.data, pageSlug: props.pageSlug }
        : { data: block.data };
}
</script>

<template>
    <template v-for="block in blocks" :key="block.id">
        <component
            :is="components[block.type as keyof typeof components]"
            v-if="block.type in components"
            v-bind="propsFor(block) as any"
        />
    </template>
</template>
