<script setup>
import {ref} from 'vue'
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot
} from '@headlessui/vue'
import {Bars3Icon, BellIcon, UserCircleIcon, XMarkIcon} from '@heroicons/vue/24/outline'
import {ChevronDownIcon} from '@heroicons/vue/20/solid'
import Navigation from "@/Components/Layout/Navigation.vue";
import NavigationList from "@/Composables/Navigation/NavigationList.js";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import {Link, usePage} from "@inertiajs/vue3";
import FlashMessage from "@/Components/Flash/FlashMessage.vue";
import ClearLogsButton from "@/Components/Buttons/ClearLogsButton.vue";

const {NavList, isNavActive} = NavigationList();
const sidebarOpen = ref(false);
const showNotifications = ref(false);
const userNavDropDownIsVisible = ref(false);
</script>

<template>
    <div class="bg-gray-100">
        <TransitionRoot as="template" :show="sidebarOpen">
            <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
                <TransitionChild as="template" enter="transition-opacity ease-linear duration-300"
                                 enter-from="opacity-0" enter-to="opacity-100"
                                 leave="transition-opacity ease-linear duration-300" leave-from="opacity-100"
                                 leave-to="opacity-0">
                    <div class="fixed inset-0 bg-gray-900/80"/>
                </TransitionChild>

                <div class="fixed inset-0 flex">
                    <TransitionChild as="template" enter="transition ease-in-out duration-300 transform"
                                     enter-from="-translate-x-full" enter-to="translate-x-0"
                                     leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0"
                                     leave-to="-translate-x-full">
                        <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                            <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0"
                                             enter-to="opacity-100" leave="ease-in-out duration-300"
                                             leave-from="opacity-100" leave-to="opacity-0">
                                <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                                    <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                                        <span class="sr-only">Close sidebar</span>
                                        <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true"/>
                                    </button>
                                </div>
                            </TransitionChild>
                            <!-- Sidebar component, swap this element with another sidebar if you like -->
                            <div
                                class="flex grow flex-col gap-y-2 overflow-y-auto bg-[#1e3757] px-6 pb-4 ring-1 ring-white/10">
                                <div class="flex h-16 shrink-0 items-center">
                                    <ApplicationLogo class="w-auto h-8 mx-auto"/>
                                </div>

                                <nav class="flex flex-1 flex-col">
                                    <ul role="list" class="flex flex-1 flex-col gap-y-4">
                                        <Navigation></Navigation>
                                        <Navigation section="administration" heading="Administration"></Navigation>
                                        <Navigation section="terminated" heading="Terminated"></Navigation>
                                        <Navigation section="logs" heading="Logs"></Navigation>
                                        <Navigation section="docs" heading="Documentation"></Navigation>
                                        <Navigation section="tools" heading="Tools"></Navigation>
                                    </ul>
                                </nav>

                                <ClearLogsButton></ClearLogsButton>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div class="flex grow flex-col gap-y-2 overflow-y-auto bg-[#1e3757] px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <ApplicationLogo class="w-auto h-8 mx-auto"/>
                </div>

                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-4">
                        <Navigation></Navigation>
                        <Navigation section="administration" heading="Administration"></Navigation>
                        <Navigation section="terminated" heading="Terminated"></Navigation>
                        <Navigation section="logs" heading="Logs"></Navigation>
                        <Navigation section="docs" heading="Documentation"></Navigation>
                        <Navigation section="tools" heading="Tools"></Navigation>
                    </ul>
                </nav>

                <ClearLogsButton></ClearLogsButton>
            </div>
        </div>

        <div class="lg:pl-72">
            <div
                class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <Bars3Icon class="h-6 w-6" aria-hidden="true"/>
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-gray-900/10 lg:hidden" aria-hidden="true"/>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">

                    <div class="relative flex flex-1 items-center">
                        <header v-if="$slots.header">
                            <slot name="header"/>
                        </header>
                    </div>

                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <template v-if="showNotifications">
                            <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
                                <span class="sr-only">View notifications</span>
                                <BellIcon class="h-6 w-6" aria-hidden="true"/>
                            </button>

                            <!-- Separator -->
                            <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-900/10" aria-hidden="true"/>
                        </template>

                        <!-- Profile dropdown -->
                        <div>
                            <div class="-m-1.5 flex items-center p-1.5 cursor-pointer"
                                 @click="userNavDropDownIsVisible = !userNavDropDownIsVisible"
                                 v-click-outside="() => userNavDropDownIsVisible = false"
                            >
                                <span class="sr-only">Open user menu</span>

                                <UserCircleIcon class="h-8 w-8 text-gray-400 rounded-full"></UserCircleIcon>

                                <span class="hidden lg:flex lg:items-center">
                                  <span class="ml-4 text-sm font-semibold leading-6 text-gray-400"
                                        aria-hidden="true" v-text="$page.props.auth.user.data.name"></span>
                                  <ChevronDownIcon class="ml-2 h-5 w-5 text-gray-400" aria-hidden="true"/>
                                </span>
                            </div>

                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">

                                <div v-if="userNavDropDownIsVisible"
                                     class="absolute right-5 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none">
                                    <template v-for="item in NavList.user_navigation" :key="item.name">
                                        <ResponsiveNavLink :href="item.path" v-text="item.name" class="no-underline"
                                                           method="post" as="button"
                                                           v-if="item.method && item.method === 'post'"
                                        >
                                        </ResponsiveNavLink>
                                        <ResponsiveNavLink :href="item.path" v-text="item.name" class="no-underline"
                                                           v-else
                                        >
                                        </ResponsiveNavLink>
                                    </template>
                                </div>

                            </transition>
                        </div>
                    </div>
                </div>
            </div>

            <FlashMessage></FlashMessage>

            <main class="h-screen">
                <div class="px-4 sm:px-6 lg:px-8 bg-gray-100 pb-14">
                    <slot/>
                </div>
            </main>
        </div>
    </div>
</template>

