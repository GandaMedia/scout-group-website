<script setup lang="ts">
import ContactEnquiryController from '@/actions/App/Http/Controllers/ContactEnquiryController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { AppPageProps } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';
import type { ContactFormBlockData } from './types';

const props = defineProps<{
    data: ContactFormBlockData;
}>();

const page = usePage<AppPageProps>();
const turnstileContainer = ref<HTMLElement | null>(null);
const widgetId = ref<string | null>(null);

const form = useForm({
    name: '',
    email: '',
    message: '',
    turnstile_token: '',
});

const successMessage = computed(() => page.props.flash.contactEnquirySubmitted);

async function loadTurnstileScript(): Promise<void> {
    if (window.turnstile) {
        return;
    }

    const existingScript = document.querySelector<HTMLScriptElement>(
        'script[data-turnstile-script]',
    );

    if (existingScript) {
        if (existingScript.dataset.loaded === 'true') {
            return;
        }

        await new Promise<void>((resolve) => {
            existingScript.addEventListener('load', () => resolve(), {
                once: true,
            });
        });

        return;
    }

    await new Promise<void>((resolve, reject) => {
        const script = document.createElement('script');
        script.src =
            'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.dataset.turnstileScript = 'true';
        script.addEventListener(
            'load',
            () => {
                script.dataset.loaded = 'true';
                resolve();
            },
            { once: true },
        );
        script.addEventListener(
            'error',
            () => reject(new Error('Unable to load Cloudflare Turnstile.')),
            { once: true },
        );

        document.head.appendChild(script);
    });
}

async function renderTurnstile(): Promise<void> {
    if (!props.data.is_configured || !props.data.turnstile_site_key) {
        return;
    }

    await nextTick();
    await loadTurnstileScript();

    if (!turnstileContainer.value || !window.turnstile) {
        return;
    }

    if (widgetId.value !== null) {
        window.turnstile.remove(turnstileContainer.value);
        turnstileContainer.value.innerHTML = '';
    }

    widgetId.value = window.turnstile.render(turnstileContainer.value, {
        sitekey: props.data.turnstile_site_key,
        callback: (token: string) => {
            form.turnstile_token = token;
        },
        'expired-callback': () => {
            form.turnstile_token = '';
        },
        'error-callback': () => {
            form.turnstile_token = '';
        },
    });
}

function submit(): void {
    form.submit(ContactEnquiryController(), {
        preserveScroll: true,
        onSuccess: async () => {
            form.reset('name', 'email', 'message', 'turnstile_token');
            await renderTurnstile();
        },
    });
}

onMounted(async () => {
    await renderTurnstile();
});
</script>

<template>
    <section class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-14">
        <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
            <div
                class="bg-scout-yellow px-6 py-8 text-scout-purple lg:px-8 lg:py-10"
            >
                <p class="text-sm font-black tracking-[0.24em] uppercase">
                    {{ data.eyebrow }}
                </p>
                <h2
                    class="mt-3 text-3xl font-extrabold text-balance lg:text-5xl"
                >
                    {{ data.title }}
                </h2>
                <p class="mt-5 text-lg leading-8 text-scout-purple/85">
                    {{ data.intro }}
                </p>
            </div>

            <div
                class="bg-white px-6 py-8 shadow-sm ring-1 ring-scout-purple/10 lg:px-8 lg:py-10"
            >
                <div
                    v-if="successMessage"
                    class="mb-6 rounded-lg border border-scout-green/25 bg-scout-green/10 px-4 py-3 text-sm font-medium text-scout-navy"
                >
                    {{ successMessage }}
                </div>

                <div
                    v-if="!data.is_configured"
                    class="rounded-lg border border-scout-red/20 bg-scout-red/10 px-4 py-3 text-sm text-scout-navy"
                >
                    The contact form is not configured yet. Please update the
                    group profile in the admin area.
                </div>

                <form v-else class="space-y-5" @submit.prevent="submit">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="contact-name">Name</Label>
                            <Input
                                id="contact-name"
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                placeholder="Your name"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="contact-email">Email</Label>
                            <Input
                                id="contact-email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                placeholder="you@example.com"
                            />
                            <InputError :message="form.errors.email" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="contact-message">Message</Label>
                        <Textarea
                            id="contact-message"
                            v-model="form.message"
                            class="min-h-40"
                            placeholder="Tell us a little about what you need help with."
                        />
                        <InputError :message="form.errors.message" />
                    </div>

                    <div class="space-y-2">
                        <div ref="turnstileContainer" />
                        <InputError
                            :message="
                                (
                                    form.errors as Record<
                                        string,
                                        string | undefined
                                    >
                                ).turnstile
                            "
                        />
                        <InputError :message="form.errors.turnstile_token" />
                    </div>

                    <InputError
                        :message="
                            (form.errors as Record<string, string | undefined>)
                                .form
                        "
                    />

                    <Button
                        type="submit"
                        class="w-full bg-scout-purple text-white hover:bg-scout-navy"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Sending...' : data.submit_label }}
                    </Button>
                </form>
            </div>
        </div>
    </section>
</template>
