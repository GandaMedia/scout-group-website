<script setup lang="ts">
import { computed } from 'vue';
import type { GoogleMapBlockData } from './types';

const props = defineProps<{
    data: GoogleMapBlockData;
}>();

const mapsSearchUrl = computed(() => {
    if (!props.data.map_address) {
        return null;
    }

    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(
        props.data.map_address,
    )}`;
});
</script>

<template>
    <section class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-14">
        <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-stretch">
            <div
                class="min-h-[420px] overflow-hidden bg-scout-navy shadow-sm ring-1 ring-scout-purple/10 lg:h-full"
            >
                <iframe
                    v-if="data.has_map && data.map_embed_url"
                    :src="data.map_embed_url"
                    :title="data.map_label ?? data.title"
                    class="block h-full min-h-[420px] w-full border-0"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                />

                <div
                    v-else
                    class="flex min-h-[420px] items-center justify-center bg-linear-to-br from-scout-navy via-scout-purple to-scout-blue px-8 text-center text-white"
                >
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.24em] text-scout-yellow uppercase"
                        >
                            Google Map
                        </p>
                        <p class="mt-4 text-lg leading-8 text-white/85">
                            Add a Google Maps embed URL in the admin contact
                            settings to show the map here.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-scout-purple px-6 py-8 text-white lg:px-8 lg:py-10">
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
                <p class="mt-5 text-lg leading-8 text-white/88">
                    {{ data.intro }}
                </p>

                <div class="mt-8 rounded-2xl bg-white/10 px-5 py-5">
                    <p class="text-lg font-black">
                        {{ data.map_label || 'Visit us' }}
                    </p>
                    <p
                        class="mt-3 text-base leading-7 whitespace-pre-line text-white/88"
                    >
                        {{
                            data.map_address ||
                            'Update the group profile to add the full address.'
                        }}
                    </p>
                </div>

                <a
                    v-if="mapsSearchUrl"
                    :href="mapsSearchUrl"
                    target="_blank"
                    rel="noreferrer"
                    class="mt-6 inline-flex items-center bg-scout-yellow px-6 py-3 text-base font-black text-scout-purple transition hover:bg-white"
                >
                    Open in Google Maps
                </a>
            </div>
        </div>
    </section>
</template>
