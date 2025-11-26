<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import LayoutBox from "@/Components/Layout/LayoutBox.vue";
import {Tabs, Tab} from "vue3-tabs-component";
import 'vue-json-pretty/lib/styles.css';
import DateFormatter from "@/Components/DateFormatter.vue";
import {ExclamationTriangleIcon, ArrowLongRightIcon, QuestionMarkCircleIcon} from "@heroicons/vue/24/solid/index.js";
import {computed} from "vue";
import Phone from "@/Mixins/Phone.js";
import PhoneLineAudioTab from "@/Pages/Auth/Competitions/PhoneLineAudioTab.vue";
import Info from "@/Components/Layout/Info.vue";
import TipHeadless from "@/Components/Tip/TipHeadless.vue";

const {formatNumber} = Phone();

const props = defineProps({
    competition: Object,
    statistics: Object,
    phoneBookEntries: Object
});

const hasPhoneLines = computed(() => props.competition.data.phone_lines.length > 0);

const hasAudio = computed(() => props.competition.data.files.length > 0);

const phoneBook = (props.phoneBookEntries.data ?? []).reduce((acc, entry) => {
    const number = entry.attributes.phone_number;
    const name = entry.attributes.name?.trim();

    acc[number] = name ? name : number; // Use name if available, otherwise number
    return acc;
}, {});

const healthSummaryColor = computed(() => {
    if (props.statistics.total.entries === 0) {
        return 'text-black';
    }

    if (props.statistics.total.health >= 80) {
        return 'text-green-500';
    }

    if (props.statistics.total.health >= 50) {
        return 'text-amber-500';
    }

    return 'text-red-500';
})

const sortedReasons = computed(() => {
    const reasons = props.statistics.fail.reasons || {};

    return Object.fromEntries(
        Object.entries(reasons)
            .sort((a, b) => b[1] - a[1])
            .map(([key, value]) => [key, value.toLocaleString()])
    );
});

const failDescriptions = {
    COMP_CLOSED: 'The competition in question is closed.',

    EARLY_HANGUP_COMP_CLOSED: 'The caller hung up during - This competition is currently closed message.',
    EARLY_HANGUP_API_CALL_START: 'The caller hung up during the first API call, before a response was been received.',
    EARLY_HANGUP_DTMF_COLLECTION: 'The caller hung up after the DTMF menu readout.',
    EARLY_HANGUP_DTMF_INVALID: 'The caller hung up early after an incorrect DTMF entry.',
    EARLY_HANGUP_COMP_OPEN: 'The caller hung up before listening to the CLI readout message.',
    EARLY_HANGUP_CLI_READOUT: 'The caller hung up during the CLI readout message.',
    EARLY_HANGUP_TOO_MANY: 'The caller hung up during the - You have entered too many times message.',
    EARLY_HANGUP_DTMF_OP2: 'The caller hung up after selecting option 2.',
    EARLY_HANGUP_HANDLE_RESPONSE: 'The caller hung up while handling the response - before any message was played.',

    DTMF_OP2: 'The caller chose DTMF option 2.',
    DTMF_INVALID: 'The callers DTMF input was outside the expected options.',
    TOO_MANY: 'The caller has made too many attempts to enter this competition.',

    DTMF_TIMEOUT: 'Caller failed to select an option.',
    DTMF_INVALID_0: 'Caller selected invalid option 0.',
    DTMF_INVALID_3: 'Caller selected invalid option 3.',
    DTMF_INVALID_4: 'Caller selected invalid option 4.',
    DTMF_INVALID_5: 'Caller selected invalid option 5.',
    DTMF_INVALID_6: 'Caller selected invalid option 6.',
    DTMF_INVALID_7: 'Caller selected invalid option 7.',
    DTMF_INVALID_8: 'Caller selected invalid option 8.',
    DTMF_INVALID_9: 'Caller selected invalid option 9.',
    'DTMF_INVALID_*': 'Caller selected invalid option *.',
    'DTMF_INVALID_#': 'Caller selected invalid option #.',
};

</script>

<template>
    <Head title="Competition"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <span class="text-gray-500 font-normal line-clamp-2">{{ props.competition.data.name }}</span>
            </h2>
        </template>

        <LayoutBox>
            <div class="flex justify-end">
                <Link :href="route('web.competition.index')" as="button" class="btn btn--sm btn--blue">
                    All Competitions
                </Link>
            </div>
        </LayoutBox>

        <LayoutBox>
            <template v-if="!hasPhoneLines">
                <div class="flex justify-center gap-4 bg-red-50 p-2 border border-red-200 rounded-lg mb-4">
                    <ExclamationTriangleIcon class="h-7 w-7 text-red-400"></ExclamationTriangleIcon>

                    <p class="text-red-600">
                        This competition has no phone lines!
                    </p>
                </div>
            </template>

            <Tabs>
                <Tab name="Competition">
                    <div class="grid grid-cols-10 gap-y-4 text-sm gap-2 border p-2 rounded-lg">
                        <p class="col-span-2 xl:col-span-1 text-gray-400 font-bold">ID:</p>
                        <p class="col-span-8 xl:col-span-9" v-text="props.competition.data.id"></p>

                        <p class="col-span-2 xl:col-span-1 text-gray-400 font-bold">Name:</p>
                        <p class="col-span-8 xl:col-span-4" v-text="props.competition.data.name"></p>

                        <p class="col-span-2 xl:col-span-1 text-gray-400 font-bold">Duration:</p>
                        <div class="col-span-8 xl:col-span-4 flex gap-x-1 ">
                            <DateFormatter :date="props.competition.data.start" format="do MMM yyyy HH:mmaaa">
                            </DateFormatter>

                            <ArrowLongRightIcon class="text-gray-400 w-4"></ArrowLongRightIcon>

                            <DateFormatter :date="props.competition.data.end" format="do MMM yyyy HH:mmaaa">
                            </DateFormatter>
                        </div>

                        <p class="col-span-2 xl:col-span-1 text-gray-400 font-bold">Max Paid Entries:</p>
                        <p class="col-span-8 xl:col-span-4" v-text="props.competition.data.max_paid_entries"></p>

                        <p class="col-span-2 xl:col-span-1 text-gray-400 font-bold">Entries Warning:</p>
                        <p class="col-span-8 xl:col-span-4" v-text="props.competition.data.entries_warning"></p>

                        <p class="col-span-2 xl:col-span-1 text-gray-400 font-bold">Special Offer:</p>
                        <p class="col-span-8 xl:col-span-4" v-text="props.competition.data.special_offer"></p>
                    </div>
                </Tab>

                <Tab name="Statistics">
                    <template v-if="props.statistics.length === 0">
                        <Info class="mt-4">
                            No statistics to display.
                        </Info>
                    </template>

                    <template v-else>
                        <Info class="mt-4">
                            Statistics results are cached for 1 minute.
                        </Info>

                        <div :class="'grid grid-cols-6 gap-1 border p-2 rounded-lg font-bold ' + healthSummaryColor">
                            <p class="col-span-2 md:col-span-1">Health Summary</p>
                            <p class="col-span-4 md:col-span-5" v-text="props.statistics.total.health +'%'"></p>
                        </div>

                        <div class="grid grid-cols-6 mt-4 gap-1 border p-2 rounded-lg">
                            <p class="col-span-2 md:col-span-1 text-gray-500">Total Calls</p>
                            <p class="col-span-4 md:col-span-5" v-text="props.statistics.total.entries"></p>

                            <p class="col-span-6 mt-6 text-gray-500">Successful</p>

                            <p class="col-span-2 md:col-span-1 pl-4">Entries</p>
                            <p class="col-span-4 md:col-span-5" v-text="props.statistics.success.entries"></p>

                            <p class="col-span-2 md:col-span-1 pl-4 text-gray-400">Paid Entries</p>
                            <p class="col-span-4 md:col-span-5 text-gray-400"
                               v-text="props.statistics.success.paid_entries"></p>

                            <p class="col-span-2 md:col-span-1 pl-4">Latest</p>
                            <p class="col-span-4 md:col-span-5" v-text="props.statistics.success.latest"></p>

                            <p class="col-span-6 mt-6 text-gray-500">Non Entries</p>

                            <p class="col-span-2 md:col-span-1 pl-4">Calls</p>
                            <p class="col-span-4 md:col-span-5" v-text="props.statistics.fail.entries"></p>

                            <p class="col-span-2 md:col-span-1 pl-4">Latest</p>
                            <p class="col-span-4 md:col-span-5" v-text="props.statistics.fail.latest"></p>

                            <p class="col-span-6 mt-6 text-gray-500" v-if="props.statistics.fail.entries > 0">Reasons For Non Entry</p>

                            <template v-if="props.statistics.fail.entries === 0">
                                <p class="col-span-6 pl-4" v-text="'N/A'"></p>
                            </template>

                            <div class="col-span-6 w-full lg:w-2/3">
                                <template v-for="(value, key) in sortedReasons">
                                    <div class="col-span-6 flex justify-between">
                                        <p class="pl-4" v-text="key + ' (' + value + ')'"></p>
                                        <TipHeadless :description="failDescriptions[key]" v-if="failDescriptions[key]">
                                            <QuestionMarkCircleIcon
                                                class="w-5 h-5 text-blue-300"></QuestionMarkCircleIcon>
                                        </TipHeadless>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="mt-4">
                        <Link :href="route('web.competition.clear-stats-cache', {competition:props.competition.data.id})" as="button" class="btn btn--sm">
                            Kill Cache
                        </Link>
                    </div>
                </Tab>

                <Tab
                    v-if="hasPhoneLines"
                    :name="'Phone Lines ('+props.competition.data.phone_lines.length+')'"
                >
                    <Tabs>
                        <template v-if="Object.keys(phoneBook).length > 0"
                                  v-for="(phoneLine, index) in props.competition.data.phone_lines">


                            <Tab
                                :name="formatNumber(phoneLine.attributes.number)  + '<br />' + phoneBook[phoneLine.attributes.number] || 'Unknown Number '+ index">
                                <div class="flex justify-between mb-4 mt-4 sm:mt-0">
                                    <span class="border rounded-md p-2 text-sm text-gray-400">
                                        Phone Number: {{ formatNumber(phoneLine.attributes.number) }}
                                    </span>

                                    <span class="border rounded-md p-2 text-sm text-gray-400">
                                        Phone Line ID: {{ phoneLine.id }}
                                    </span>
                                </div>

                                <PhoneLineAudioTab
                                    :default-audio="props.competition.data.default_audio"
                                    :phone-line-data="phoneLine"
                                    :competition-files="props.competition.data.files"
                                >
                                </PhoneLineAudioTab>
                            </Tab>
                        </template>
                    </Tabs>
                </Tab>
            </Tabs>
        </LayoutBox>
    </AuthenticatedLayout>
</template>
