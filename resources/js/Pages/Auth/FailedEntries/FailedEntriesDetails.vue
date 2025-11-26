<script setup>
import Phone from "@/Mixins/Phone.js"
import DateFormatter from "@/Components/DateFormatter.vue";
import CopyToClipboard from "@/Components/Clipboard/CopyToClipboard.vue";

const {formatNumber} = Phone();

const props = defineProps({
    failedEntry: Object,
    even: Boolean,
    index: Number,
});

</script>

<template>
    <div>
        <div class="border border-gray-100 xl:border-none rounded-lg px-0 text-sm"
             :class="{'mt-4 xl:mt-0' : index > 0}">
            <div class="xl:hidden flex justify-between bg-gray-100 p-2 rounded-t-lg">
                <p class="ml-auto text-gray-600 text-sm" v-text="'ID: ' + props.failedEntry.id"></p>
            </div>

            <div class="grid grid-cols-3 xl:grid-cols-12 gap-y-2 xl:gap-4 px-4 xl:px-2 py-4 xl:py-2"
                 :class="{'xl:bg-gray-100' : even}">

                <div class="hidden xl:inline-block col-span-2 xl:col-span-2 gap-2 text-gray-400 text-xs">
                    <p class="text-sm " v-text="'ID: ' + props.failedEntry.id"></p>

                    <div v-if="props.failedEntry.competition_id">
                        <Link
                            :href="route('web.competition.show', {competition: props.failedEntry.competition_id})"
                            class="text-blue-600 underline">{{ 'Comp ID: ' + props.failedEntry.competition_id }}
                        </Link>
                    </div>
                    <p v-text="'Comp ID: -'" v-else></p>

                    <CopyToClipboard
                        styling="mt-1 border border-blue-500 rounded-md text-blue-500 hover:border-blue-800 hover:text-blue-800 px-1"
                        :button-title="'Call ID: '+props.failedEntry.call_id"
                        :copy="props.failedEntry.call_id">
                    </CopyToClipboard>
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Competition ID:</div>
                <div class="block xl:hidden col-span-2 xl:col-span-1 xl:text-center">
                    <Link v-if="props.failedEntry.competition_id"
                          :href="route('web.competition.show', {competition: props.failedEntry.competition_id})"
                          class="text-blue-600 underline">{{ props.failedEntry.competition_id }}
                    </Link>
                    <p v-text="props.failedEntry.competition_id" v-else></p>
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Call ID:</div>
                <div class="block xl:hidden col-span-2 xl:col-span-2 xl:text-center">
                    {{ props.failedEntry.call_id }}
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Competition Phone Number:</div>
                <div class="col-span-2 xl:col-span-2 xl:text-center">
                    {{ formatNumber(props.failedEntry.phone_number) }}
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Caller Phone Number:</div>
                <div class="col-span-2 xl:col-span-2 xl:text-center">
                    {{ formatNumber(props.failedEntry.caller_phone_number) }}
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Reason:</div>
                <div class="col-span-2 xl:col-span-2 text-left xl:text-center">
                    <p
                        class="inline-block bg-gray-500 text-white px-2 py-1 break-all rounded-lg text-xs"
                        v-text="props.failedEntry.reason"
                    ></p>
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Call Start:</div>
                <div class="col-span-2 xl:col-span-2 xl:text-center text-left">
                    <DateFormatter :date="failedEntry.call_start" format="do MMM yyyy HH:mm:ss"></DateFormatter>
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Organisation:</div>
                <div class="col-span-2 xl:col-span-2 xl:text-center text-left">
                    <p class="text-sm">
                        {{ props.failedEntry.organisation_name }}
                        <span class="text-gray-500">({{ props.failedEntry.organisation_id }})</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
