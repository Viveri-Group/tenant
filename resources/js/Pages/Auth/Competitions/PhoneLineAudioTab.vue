<script setup>
import Tip from "@/Components/Tip/Tip.vue";
import PhoneLineAudioDetails from "@/Pages/Auth/Competitions/PhoneLineAudioDetails.vue";

const props = defineProps({
    phoneLineData: Object,
    defaultAudio: Object,
    competitionFiles: Object,
})

let competitionAudio = props.competitionFiles
    .filter((item) => item.attributes.competition_phone_line_id === null)
    .reduce((acc, item) => {
        acc[item.attributes.type] = item.attributes.external_id;
        return acc;
    }, {});

const phoneLineAudio = props.competitionFiles
    .filter((item) => item.attributes.competition_phone_line_id === props.phoneLineData.id)
    .reduce((acc, item) => {
        acc[item.attributes.type] = item.attributes.external_id;
        return acc;
    }, {});

const filteredAudio = {
    ...props.defaultAudio, // lowest priority
    ...competitionAudio,   // middle priority
    ...phoneLineAudio      // highest priority
}

const suppliedBy = (external_id) => {
    if(Object.values(phoneLineAudio).includes(external_id)){
        return 'phone line'
    }

    if(Object.values(competitionAudio).includes(external_id)){
        return 'competition'
    }

    return 'default';
}

const finalisedAudioData = Object.entries(filteredAudio).map(([audio_type, external_id]) => ({
    audio_type,
    external_id,
    supplied_by: suppliedBy(external_id)
}));
</script>

<template>
    <div class="border rounded p-4">
        <p class="font-bold text-blue-600">Audio</p>

        <div class="hidden xl:block mt-4">
            <div
                class="grid grid-cols-12 gap-4 p-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 dark:text-gray-400"
            >
                <p class="col-span-2">
                    SHOUT ID
                    <tip description="ID of the audio on the Shout Server."></tip>
                </p>

                <p class="col-span-3">
                    Type
                    <tip description="The audio type."></tip>
                </p>
            </div>
        </div>
        <template v-for="(data, index) in finalisedAudioData">
            <PhoneLineAudioDetails
                :data="data"
                :even="!!(index % 2)"
                :index="index"
            >
            </PhoneLineAudioDetails>
        </template>
    </div>
</template>

