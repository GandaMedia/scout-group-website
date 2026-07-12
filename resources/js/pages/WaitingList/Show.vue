<script setup lang="ts">
import WaitingListEntryController from '@/actions/App/Http/Controllers/WaitingListEntryController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

interface WaitingListSectionPageData {
    slug: string;
    label: string;
    ageRange: string;
    title: string;
    intro: string;
}

interface WaitingListFormConfig {
    turnstile_site_key?: string | null;
    is_configured: boolean;
}

const props = defineProps<{
    section: WaitingListSectionPageData;
    form: WaitingListFormConfig;
}>();

const page = usePage<AppPageProps>();
const turnstileContainer = ref<HTMLElement | null>(null);
const widgetId = ref<string | null>(null);

const submissionForm = useForm({
    first_name: '',
    last_name: '',
    date_of_birth: '',
    section_slug: props.section.slug,
    parent_name: '',
    parent_email: '',
    parent_phone: '',
    postcode: '',
    notes: '',
    turnstile_token: '',
});

const successMessage = computed(() => page.props.flash.waitingListSubmitted);

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
            {
                once: true,
            },
        );

        document.head.appendChild(script);
    });
}

async function renderTurnstile(): Promise<void> {
    if (!props.form.is_configured || !props.form.turnstile_site_key) {
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
        sitekey: props.form.turnstile_site_key,
        callback: (token: string) => {
            submissionForm.turnstile_token = token;
        },
        'expired-callback': () => {
            submissionForm.turnstile_token = '';
        },
        'error-callback': () => {
            submissionForm.turnstile_token = '';
        },
    });
}

function submit(): void {
    submissionForm.submit(WaitingListEntryController(), {
        preserveScroll: true,
        onSuccess: async () => {
            submissionForm.reset(
                'first_name',
                'last_name',
                'date_of_birth',
                'parent_name',
                'parent_email',
                'parent_phone',
                'postcode',
                'notes',
                'turnstile_token',
            );
            submissionForm.section_slug = props.section.slug;
            await renderTurnstile();
        },
    });
}

onMounted(async () => {
    await renderTurnstile();
});
</script>

<template>
    <AppLayout>
        <Head :title="section.title" />

        <main
            class="bg-linear-to-b from-white via-scout-pink/10 to-white pb-16"
        >
            <section class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-14">
                <div
                    class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr] lg:items-start"
                >
                    <div
                        class="bg-scout-yellow px-6 py-8 text-scout-purple lg:px-8 lg:py-10"
                    >
                        <p
                            class="text-sm font-black tracking-[0.24em] uppercase"
                        >
                            {{ section.label }} • {{ section.ageRange }}
                        </p>
                        <h1
                            class="mt-3 text-3xl font-extrabold text-balance lg:text-5xl"
                        >
                            {{ section.title }}
                        </h1>
                        <p class="mt-5 text-lg leading-8 text-scout-purple/85">
                            {{ section.intro }}
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
                            v-if="!form.is_configured"
                            class="rounded-lg border border-scout-red/20 bg-scout-red/10 px-4 py-3 text-sm text-scout-navy"
                        >
                            The waiting list form is not configured yet. Please
                            update the site settings in the admin area.
                        </div>

                        <form v-else class="space-y-5" @submit.prevent="submit">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="child-first-name"
                                        >Child first name</Label
                                    >
                                    <Input
                                        id="child-first-name"
                                        v-model="submissionForm.first_name"
                                        type="text"
                                        autocomplete="given-name"
                                    />
                                    <InputError
                                        :message="
                                            submissionForm.errors.first_name
                                        "
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="child-last-name"
                                        >Child last name</Label
                                    >
                                    <Input
                                        id="child-last-name"
                                        v-model="submissionForm.last_name"
                                        type="text"
                                        autocomplete="family-name"
                                    />
                                    <InputError
                                        :message="
                                            submissionForm.errors.last_name
                                        "
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="child-dob">Date of birth</Label>
                                <Input
                                    id="child-dob"
                                    v-model="submissionForm.date_of_birth"
                                    type="date"
                                />
                                <InputError
                                    :message="
                                        submissionForm.errors.date_of_birth
                                    "
                                />
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="parent-name"
                                        >Parent / carer name</Label
                                    >
                                    <Input
                                        id="parent-name"
                                        v-model="submissionForm.parent_name"
                                        type="text"
                                        autocomplete="name"
                                    />
                                    <InputError
                                        :message="
                                            submissionForm.errors.parent_name
                                        "
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="parent-email"
                                        >Parent / carer email</Label
                                    >
                                    <Input
                                        id="parent-email"
                                        v-model="submissionForm.parent_email"
                                        type="email"
                                        autocomplete="email"
                                    />
                                    <InputError
                                        :message="
                                            submissionForm.errors.parent_email
                                        "
                                    />
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="parent-phone"
                                        >Parent / carer phone</Label
                                    >
                                    <Input
                                        id="parent-phone"
                                        v-model="submissionForm.parent_phone"
                                        type="tel"
                                        autocomplete="tel"
                                    />
                                    <InputError
                                        :message="
                                            submissionForm.errors.parent_phone
                                        "
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="postcode">Postcode</Label>
                                    <Input
                                        id="postcode"
                                        v-model="submissionForm.postcode"
                                        type="text"
                                        autocomplete="postal-code"
                                    />
                                    <InputError
                                        :message="
                                            submissionForm.errors.postcode
                                        "
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="notes"
                                    >Anything we should know?</Label
                                >
                                <Textarea
                                    id="notes"
                                    v-model="submissionForm.notes"
                                    class="min-h-32"
                                    placeholder="Tell us about availability, accessibility needs, previous Scouting experience, or anything else that would help the leaders."
                                />
                                <InputError
                                    :message="submissionForm.errors.notes"
                                />
                            </div>

                            <div class="space-y-2">
                                <div ref="turnstileContainer" />
                                <InputError
                                    :message="
                                        (
                                            submissionForm.errors as Record<
                                                string,
                                                string | undefined
                                            >
                                        ).turnstile
                                    "
                                />
                                <InputError
                                    :message="
                                        submissionForm.errors.turnstile_token
                                    "
                                />
                            </div>

                            <InputError
                                :message="
                                    (
                                        submissionForm.errors as Record<
                                            string,
                                            string | undefined
                                        >
                                    ).form
                                "
                            />

                            <Button
                                type="submit"
                                class="w-full bg-scout-purple text-white hover:bg-scout-navy"
                                :disabled="submissionForm.processing"
                            >
                                {{
                                    submissionForm.processing
                                        ? 'Sending...'
                                        : `Join ${section.label}`
                                }}
                            </Button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
