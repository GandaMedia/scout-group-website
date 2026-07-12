<template>
    <div class="mt-8 w-full bg-scout-purple py-12 lg:mt-12 lg:py-16">
        <div
            class="mx-auto grid max-w-7xl grid-cols-1 gap-6 text-white lg:grid-cols-4"
        >
            <div class="px-4 lg:col-span-2 lg:px-0">
                <GroupLogo class="mx-auto h-16 text-white lg:mx-0 lg:h-24" />
            </div>

            <div class="mt-2 px-4 text-center lg:px-0 lg:text-left">
                <h4 class="pb-2 text-lg font-black">Useful Links</h4>
                <ul>
                    <li class="mb-1">
                        <a href="https://www.scouts.org.uk/" target="_blank"
                            >Scouts UK</a
                        >
                    </li>
                    <li v-if="!auth.user" class="mb-1">
                        <Link :href="login()">Leader login</Link>
                    </li>
                    <li v-if="!auth.user" class="mb-1">
                        <Link :href="register()">Leader registration</Link>
                    </li>
                    <li v-if="auth.canAccessLeaderTools" class="mb-1">
                        <Link :href="projects()">Leader tools</Link>
                    </li>
                    <li
                        v-if="auth.canAccessAdmin && auth.adminUrl"
                        class="mb-1"
                    >
                        <a :href="auth.adminUrl">Admin</a>
                    </li>
                    <li class="mb-1">
                        <a :href="groupProfile.districtUrl" target="_blank">{{
                            groupProfile.districtName
                        }}</a>
                    </li>
                    <li class="mb-1">
                        <a
                            href="https://www.onlinescoutmanager.co.uk/"
                            target="_blank"
                            >Online Scout Manager (OSM)</a
                        >
                    </li>
                    <li class="mb-1">
                        <a href="https://shop.scouts.org.uk/" target="_blank"
                            >Scout Shop</a
                        >
                    </li>
                </ul>
            </div>
            <div class="mt-2 px-4 text-center lg:px-0 lg:text-left">
                <h4 class="pb-2 text-lg font-black">Useful Info</h4>
                <ul>
                    <li class="mb-1">
                        <Link :href="showPage({ page: 'privacy' })"
                            >Privacy and GDPR</Link
                        >
                    </li>
                    <li class="mb-1">
                        <Link :href="showPage({ page: 'terms' })"
                            >Terms and conditions</Link
                        >
                    </li>
                    <li class="mb-1">
                        <Link :href="showPage({ page: 'cookie-policy' })"
                            >Cookie policy</Link
                        >
                    </li>
                    <li class="mb-1">
                        {{ groupProfile.shortName }} &copy;
                        {{ new Date().getFullYear() }}
                    </li>
                    <li class="mb-1">
                        <a
                            :href="groupProfile.charityRegisterUrl"
                            target="_blank"
                            >Charity No: {{ groupProfile.charityNumber }}</a
                        >
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import GroupLogo from '@/components/logos/GroupLogo.vue';
import { login, register } from '@/routes';
import { show as showPage } from '@/routes/page';
import { projects } from '@/routes/tools';
import type { AppPageProps, GroupProfile } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<AppPageProps>();
const groupProfile = computed((): GroupProfile => page.props.groupProfile);
const auth = computed(() => page.props.auth);
</script>
