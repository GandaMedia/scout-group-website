<template>
    <div class="bg flex lg:hidden">
        <button
            type="button"
            class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700"
            @click="mobileMenuOpen = true"
        >
            <span class="sr-only">Open main menu</span>
            <Bars3Icon class="size-6" aria-hidden="true" />
        </button>
    </div>
    <PopoverGroup class="relative z-30 hidden lg:flex lg:gap-x-12">
        <template v-for="item in menu" :key="item.id">
            <Popover class="relative" v-if="item.children.length">
                <PopoverButton
                    class="flex items-center gap-x-1 text-lg/6 font-black text-gray-900 transition-colors duration-300 ease-in-out hover:text-scout-purple"
                >
                    {{ item.name }}
                    <ChevronDownIcon
                        class="size-5 flex-none text-gray-400"
                        aria-hidden="true"
                    />
                </PopoverButton>

                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <PopoverPanel
                        class="absolute left-1/2 z-50 mt-3 w-screen max-w-md -translate-x-1/2 overflow-hidden rounded-3xl bg-white shadow-lg outline-1 outline-gray-900/5"
                    >
                        <div class="p-4">
                            <div
                                v-for="childItem in item.children"
                                :key="childItem.id"
                                class="group relative flex items-center gap-x-6 rounded-lg p-4 text-sm/6 hover:bg-scout-blue/10"
                            >
                                <div class="flex-auto">
                                    <a
                                        v-if="childItem.external"
                                        :href="childItem.link"
                                        target="_blank"
                                        class="block text-lg font-bold text-gray-900"
                                        >{{ childItem.name }}
                                        <span class="absolute inset-0" />
                                    </a>
                                    <Link
                                        prefetch
                                        v-else
                                        :href="childItem.link"
                                        class="block text-lg font-bold text-gray-900"
                                        >{{ childItem.name }}
                                        <span class="absolute inset-0" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </PopoverPanel>
                </transition>
            </Popover>

            <template v-else>
                <a
                    v-if="item.external"
                    :href="item.link"
                    target="_blank"
                    class="text-lg/6 font-black text-gray-900 transition-colors duration-300 ease-in-out hover:text-scout-purple"
                    >{{ item.name }}</a
                >
                <Link
                    prefetch
                    v-else
                    :href="item.link"
                    class="text-lg/6 font-black text-gray-900 transition-colors duration-300 ease-in-out hover:text-scout-purple"
                    >{{ item.name }}</Link
                >
            </template>
        </template>
    </PopoverGroup>

    <TransitionRoot :show="mobileMenuOpen" as="template">
        <Dialog
            class="lg:hidden"
            @close="mobileMenuOpen = false"
            :open="mobileMenuOpen"
        >
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 z-50 bg-gray-500/75" />
            </TransitionChild>

            <TransitionChild
                as="template"
                enter="transform transition ease-in-out duration-300"
                enter-from="-translate-y-full"
                enter-to="translate-y-0"
                leave="transform transition ease-in-out duration-300"
                leave-from="translate-y-0"
                leave-to="-translate-y-full"
            >
                <DialogPanel
                    class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white p-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10"
                >
                    <div class="flex items-center justify-between">
                        <a href="#" class="-m-1.5 p-1.5">
                            <span class="sr-only">{{ groupProfile.name }}</span>
                            <GroupLogo class="h-12 w-auto text-scout-purple" />
                        </a>
                        <button
                            type="button"
                            class="-m-2.5 rounded-md p-2.5 text-gray-700"
                            @click="mobileMenuOpen = false"
                        >
                            <span class="sr-only">Close menu</span>
                            <XMarkIcon class="size-6" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="mt-6 flow-root">
                        <div class="-my-6 divide-y divide-gray-500/10">
                            <div class="space-y-2 py-6">
                                <template v-for="item in menu" :key="item.id">
                                    <Disclosure
                                        v-if="item.children.length"
                                        as="div"
                                        class="-mx-3"
                                        v-slot="{ open }"
                                    >
                                        <DisclosureButton
                                            class="flex w-full items-center justify-between rounded-lg py-2 pr-3.5 pl-3 text-base/7 font-semibold text-gray-900 hover:bg-gray-50"
                                        >
                                            {{ item.name }}
                                            <ChevronDownIcon
                                                :class="[
                                                    open ? 'rotate-180' : '',
                                                    'size-5 flex-none',
                                                ]"
                                                aria-hidden="true"
                                            />
                                        </DisclosureButton>
                                        <DisclosurePanel class="mt-2 space-y-2">
                                            <DisclosureButton
                                                v-for="childItem in item.children"
                                                :key="childItem.id"
                                                as="a"
                                                :href="childItem.link"
                                                class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50"
                                                >{{
                                                    childItem.name
                                                }}</DisclosureButton
                                            >
                                        </DisclosurePanel>
                                    </Disclosure>
                                    <template v-else>
                                        <a
                                            v-if="item.external"
                                            :href="item.link"
                                            target="_blank"
                                            class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50"
                                            >{{ item.name }}</a
                                        >
                                        <Link
                                            prefetch
                                            v-else
                                            :href="item.link"
                                            class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50"
                                            >{{ item.name }}</Link
                                        >
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>
                </DialogPanel>
            </TransitionChild>
        </Dialog>
    </TransitionRoot>
</template>
<script setup lang="ts">
import type { AppPageProps, GroupProfile } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<AppPageProps>();
const menu = computed((): MenuItem[] => page.props.menu as MenuItem[]);
const groupProfile = computed((): GroupProfile => page.props.groupProfile);

type MenuItem = {
    id: number;
    name: string;
    link: string;
    external: boolean;
    children: Array<MenuItem>;
};

import GroupLogo from '@/components/logos/GroupLogo.vue';
import {
    Dialog,
    DialogPanel,
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Popover,
    PopoverButton,
    PopoverGroup,
    PopoverPanel,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const mobileMenuOpen = ref(false);
</script>
