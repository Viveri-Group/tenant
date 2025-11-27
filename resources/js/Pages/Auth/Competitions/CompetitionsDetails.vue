<script setup>
import CompetitionTimeDetails from "@/Components/CompetitionTimeDetails.vue";
import { ExclamationTriangleIcon } from '@heroicons/vue/24/solid';
import TipHeadless from "@/Components/Tip/TipHeadless.vue";
import {CheckIcon, XMarkIcon} from "@heroicons/vue/24/solid/index.js";
import {computed} from "vue";
import {isAfter, isPast, isWithinInterval, parseISO} from "date-fns";

const props = defineProps({
    competition: Object,
    even: Boolean,
    index: Number,
});

const isOpen = computed(() => {
    const now = new Date();
    const start = parseISO(props.competition.start);
    const end = parseISO(props.competition.end);

    return isWithinInterval(now, { start, end });
});

const isInPast = computed(() => {
    const now = new Date();
    const end = parseISO(props.competition.end);

    return isAfter(now, end);
});

const competitionNameColor = () => {
    if(isOpen.value)
        return 'text-green-600';

    if(isInPast.value)
        return 'text-gray-400 line-through';

    return 'text-gray-800';
};
</script>

<template>
    <div>
        <div class="border border-gray-100 xl:border-none rounded-lg px-0 text-sm"
             :class="{'mt-4 xl:mt-0' : index > 0}">
            <div class="xl:hidden flex justify-between bg-gray-100 p-2 rounded-t-lg">
                <p class="text-sm" :class="competitionNameColor()">
                    {{ props.competition.name }}
                </p>
                <p class="ml-auto text-gray-600 text-sm flex-shrink-0" v-text="'ID: ' + props.competition.id"></p>
            </div>

            <div class="grid grid-cols-3 xl:grid-cols-12 gap-y-2 xl:gap-4 px-4 xl:px-2 py-4 xl:py-2"
                 :class="{'xl:bg-gray-100' : even}">

                <div class="hidden xl:flex flex-wrap gap-2">
                    <p class="text-sm " v-text="props.competition.id"></p>
                </div>

                <div class="hidden xl:hidden font-bold text-gray-400">Name:</div>
                <div class="hidden xl:block col-span-2 xl:col-span-3">
                    <p class="text-sm" :class="competitionNameColor()" v-text="props.competition.name"></p>
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Time Details:</div>
                <div class="col-span-2 xl:col-span-3 md:text-center">
                    <CompetitionTimeDetails :competition="props.competition"></CompetitionTimeDetails>
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Special Offer:</div>
                <div class="col-span-2 xl:col-span-2 md:text-center">
                    {{ props.competition.special_offer ?? '-'}}
                </div>

                <div class="col-span-3 xl:col-span-2 flex gap-4 justify-end items-center">
                    <template v-if="props.competition.phone_lines.length < 1">
                        <TipHeadless description="No phone line set.">
                            <ExclamationTriangleIcon class="h-7 w-7 text-red-500"></ExclamationTriangleIcon>
                        </TipHeadless>
                    </template>

                    <div>
                        <Link :href="route('web.competition.show', {competition: competition.id})" as="button" class="btn btn--sm btn--blue">View</Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
