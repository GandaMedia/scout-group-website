<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { calendar } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { projects } from '@/routes/tools';
import type { AppPageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    CookingPot,
    Settings,
    ShieldCheck,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<AppPageProps>();
const auth = computed(() => page.props.auth);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <main class="mx-auto min-h-[55vh] max-w-7xl px-4 py-12 lg:px-0">
            <div
                v-if="auth.approvalStatus === 'pending'"
                class="max-w-2xl border-l-4 border-scout-yellow bg-yellow-50 p-6"
            >
                <h1 class="text-3xl font-black text-scout-navy">
                    Approval pending
                </h1>
                <p class="mt-3 leading-7 text-slate-700">
                    Your email is verified and a group administrator has been
                    asked to review your leader account. You will receive an
                    email when access is approved.
                </p>
            </div>
            <div
                v-else-if="auth.approvalStatus === 'rejected'"
                class="max-w-2xl border-l-4 border-red-600 bg-red-50 p-6"
            >
                <h1 class="text-3xl font-black text-scout-navy">
                    Access not approved
                </h1>
                <p class="mt-3 leading-7 text-slate-700">
                    Your request has been reviewed and leader access is not
                    currently enabled. Contact a group administrator if you
                    believe this is incorrect.
                </p>
            </div>
            <div v-else>
                <p class="text-sm font-black text-scout-purple uppercase">
                    Leader area
                </p>
                <h1 class="mt-2 text-4xl font-black text-scout-navy">
                    Welcome back, {{ auth.user?.name }}
                </h1>
                <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-if="auth.canAccessLeaderTools"
                        :href="projects()"
                        class="border border-slate-200 p-5 transition hover:border-scout-purple"
                    >
                        <CookingPot class="size-7 text-scout-purple" />
                        <h2 class="mt-4 text-lg font-black">
                            Projects and meals
                        </h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Plan menus, costs and shopping.
                        </p>
                    </Link>
                    <Link
                        :href="calendar()"
                        class="border border-slate-200 p-5 transition hover:border-scout-purple"
                    >
                        <CalendarDays class="size-7 text-scout-purple" />
                        <h2 class="mt-4 text-lg font-black">Group calendar</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Review upcoming activities.
                        </p>
                    </Link>
                    <Link
                        :href="editProfile()"
                        class="border border-slate-200 p-5 transition hover:border-scout-purple"
                    >
                        <Settings class="size-7 text-scout-purple" />
                        <h2 class="mt-4 text-lg font-black">
                            Account settings
                        </h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Update profile and security.
                        </p>
                    </Link>
                    <a
                        v-if="auth.canAccessAdmin && auth.adminUrl"
                        :href="auth.adminUrl"
                        class="border border-slate-200 p-5 transition hover:border-scout-purple"
                    >
                        <ShieldCheck class="size-7 text-scout-purple" />
                        <h2 class="mt-4 text-lg font-black">Site admin</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Manage content and access.
                        </p>
                    </a>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
