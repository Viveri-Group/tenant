<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import LayoutBox from "@/Components/Layout/LayoutBox.vue";
import Info from "@/Components/Layout/Info.vue";
import DateFormatter from "@/Components/DateFormatter.vue";
import Phone from "@/Mixins/Phone.js"

const {formatNumber} = Phone();

const props = defineProps({
    phoneBookEntry: Object,
    lookupData: Object,
    lookupPerformed: String,
});
</script>

<template>
    <Head title="Phone Line Lookup"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <span class="text-gray-500 font-normal">Phone Line Lookup</span>
            </h2>
        </template>

        <Info class="mt-4">
            Competition Phone Line Lookup shows which competition if any is currently active on the phone line.
        </Info>

        <LayoutBox>
            <div class="flex justify-between items-center">
                <span class="block md:flex gap-2">
                    <p class="font-bold text-gray-400" v-text="formatNumber(props.phoneBookEntry.phone_number)"></p>
                    <p v-if="props.phoneBookEntry.name"
                        class="uppercase italic text-sm xl:text-base" v-text="'('+props.phoneBookEntry.name+')'">
                    </p>
                </span>
                <Link :href="route('web.phone-book-entries.index')" as="button" class="btn btn--sm btn--blue">All Phone Book Entries
                </Link>
            </div>
        </LayoutBox>

        <LayoutBox v-if="Object.keys($attrs.errors).length">
            <p class="text-red-600 text-sm">Oops!</p>

            <ul class="text-red-600 text-sm">
                <template v-for="value in $attrs.errors">
                    <template v-for="error in value">
                        <li class="list-disc ml-10" v-text="error"></li>
                    </template>
                </template>
            </ul>
        </LayoutBox>

        <LayoutBox v-else>
            <div class="mb-4 text-sm flex justify-between border text-gray-400 p-2 rounded-lg font-bold">
                <span class="">Lookup performed</span>
                <DateFormatter :date="props.lookupPerformed" format="do MMM yyyy HH:mmaaa"></DateFormatter>
            </div>

            <template v-if="props.lookupData && props.lookupData.length > 0">
                <template v-for="data in props.lookupData">
                    <div class="border rounded-xl p-4 mb-4">
                        <p class="text-blue-500 font-bold">Phone Line</p>

                        <div class="grid grid-cols-12 gap-2 px-4 text-sm">
                            <p class="col-span-4 xl:col-span-2 text-gray-400 font-bold">ID:</p>
                            <p class="col-span-8 xl:col-span-10" v-text="data.phone_line.id"></p>

                            <p class="col-span-4 xl:col-span-2 text-gray-400 font-bold">Number:</p>
                            <p class="col-span-8 xl:col-span-10" v-text="formatNumber(data.phone_line.number)"></p>
                        </div>
                    </div>

                    <div class="border rounded-xl p-4">
                        <p class="text-blue-500 font-bold">Competition</p>

                        <div class="grid grid-cols-12 gap-2 px-4 text-sm">
                            <p class="col-span-4 xl:col-span-2 text-gray-400 font-bold">ID:</p>
                            <p class="col-span-8 xl:col-span-10">
                                <Link :href="route('web.competition.show', {competition: data.competition.id})"
                                      as="button" class="text-blue-600 underline">
                                    {{ data.competition.id }}
                                </Link>
                            </p>

                            <p class="col-span-4 xl:col-span-2 text-gray-400 font-bold">Name:</p>
                            <p class="col-span-8 xl:col-span-10">
                                <Link :href="route('web.competition.show', {competition: data.competition.id})"
                                      as="button" class="text-blue-600 underline">
                                    {{ data.competition.name }}
                                </Link>

                            </p>

                            <p class="col-span-4 xl:col-span-2 text-gray-400 font-bold">Start:</p>
                            <p class="col-span-8 xl:col-span-10">
                                <DateFormatter :date="data.competition.start"
                                               format="do MMM yyyy HH:mmaaa"></DateFormatter>
                            </p>

                            <p class="col-span-4 xl:col-span-2 text-gray-400 font-bold">End:</p>
                            <p class="col-span-8 xl:col-span-10">
                                <DateFormatter :date="data.competition.end"
                                               format="do MMM yyyy HH:mmaaa"></DateFormatter>
                            </p>

<!--                            <p class="col-span-4 xl:col-span-2 text-gray-400 font-bold">SMS Offers Enabled:</p>-->
<!--                            <p class="col-span-8 xl:col-span-10">-->
<!--                                <CheckIcon class="h-5 w-5 text-green-600"-->
<!--                                           v-if="data.competition.sms_offers_enabled"></CheckIcon>-->
<!--                                <XMarkIcon class="h-5 w-5 text-red-600"-->
<!--                                           v-if="!data.competition.sms_offers_enabled"></XMarkIcon>-->
<!--                            </p>-->
                        </div>
                    </div>
                </template>
            </template>

            <p v-else class="bg-gray-100 p-4 mt-4 text-center rounded text-gray-500 text-sm">No competition data
                found.</p>
        </LayoutBox>
    </AuthenticatedLayout>
</template>
