<script setup>
import Phone from "@/Mixins/Phone.js"
import DateFormatter from "@/Components/DateFormatter.vue";
import {CheckIcon, XMarkIcon} from "@heroicons/vue/24/solid/index.js";
import CopyToClipboard from "@/Components/Clipboard/CopyToClipboard.vue";

const {formatNumber} = Phone();

const props = defineProps({
    participant: Object,
    even: Boolean,
    index: Number,
});

</script>

<template>
    <div>
        <div class="border border-gray-100 xl:border-none rounded-lg px-0 text-sm"
             :class="{'mt-4 xl:mt-0' : index > 0}">
            <div class="xl:hidden flex justify-between bg-gray-100 p-2 rounded-t-lg text-gray-600 text-sm">
                <p v-text="'ID: ' + props.participant.id"></p>
                <p v-text="'Competition ID: ' + props.participant.competition_id"></p>
            </div>

            <div class="grid grid-cols-3 xl:grid-cols-12 gap-y-2 xl:gap-4 px-4 xl:px-2 py-4 xl:py-2"
                 :class="{'xl:bg-gray-100' : even}">

                <div class="hidden xl:inline-block col-span-2 xl:col-span-2 gap-2 text-gray-400 text-xs">
                    <p class="" v-text="'ID: ' + props.participant.id"></p>

                    <div v-if="props.participant.competition_id" class="mb-0.5">
                        <Link
                            :href="route('web.competition.show', {competition: props.participant.competition_id})"
                            class="text-blue-600 underline">{{'Comp ID: ' + props.participant.competition_id}}</Link>
                    </div>

                    <p v-text="props.participant.competition_id" v-else></p>

                    <CopyToClipboard
                        styling="border border-blue-500 rounded-md text-blue-500 hover:border-blue-800 hover:text-blue-800 px-1"
                        :button-title="'Call ID: '+props.participant.call_id"
                        :copy="props.participant.call_id">
                    </CopyToClipboard>
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Call ID:</div>
                <div class="block xl:hidden col-span-2 xl:col-span-1 xl:text-center">
                    {{ props.participant.call_id}}
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Organisation:</div>
                <div class="col-span-2 xl:col-span-3 xl:text-center text-left">
                    <p class="text-sm">
                        {{props.participant.organisation_name}}
                        <span class="text-gray-500">({{props.participant.organisation_id}})</span>
                    </p>
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Competition Telephone:</div>
                <div class="col-span-2 xl:col-span-2 xl:text-center">
                    {{ formatNumber(props.participant.competition_phone_number)}}
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Caller Telephone:</div>
                <div class="col-span-2 xl:col-span-2 xl:text-center">
                    {{ formatNumber(props.participant.telephone)}}
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Call Start:</div>
                <div class="col-span-2 xl:col-span-3 xl:text-center text-left">
                    <DateFormatter :date="participant.call_start" format="do MMM yyyy HH:mm:ss"></DateFormatter>
                </div>
            </div>
        </div>
    </div>
</template>
