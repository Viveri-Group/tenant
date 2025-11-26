<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, useForm} from '@inertiajs/vue3';
import Tip from "@/Components/Tip/Tip.vue";
import LayoutBox from "@/Components/Layout/LayoutBox.vue";
import Pagination from "@/Components/Layout/Pagination.vue";
import ParticipantsSearch from "@/Pages/Auth/Participants/ParticipantsSearch.vue";
import ParticipantDetails from "@/Pages/Auth/Participants/ParticipantDetails.vue";
import Info from "@/Components/Layout/Info.vue";

const props = defineProps({
    participants: Object,
    defaultSearchFormOptions: Object
});

const form = useForm({
    competition_id: '',
    call_id:'',
    phone_number: '',
    caller_phone_number: '',
    date_from: '',
    date_to: '',
});

const updateSearchCriteria = (searchParams) => {
    Object.entries(searchParams).forEach(([key, value]) => {
        form[key] = value
    });
};
</script>

<template>
    <Head title="Participants"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <span class="text-gray-500 font-normal">Participants</span>
            </h2>
        </template>

        <Info class="mt-4">
            This shows a list of successful phone entries. For opposites to this see Non Entries.
        </Info>

        <ParticipantsSearch :default-search-form-options="props.defaultSearchFormOptions"
                        :update-search-criteria="updateSearchCriteria"></ParticipantsSearch>

        <LayoutBox>
            <template v-if="props.participants.data.data.length > 0">
                <Pagination :data="props.participants"></Pagination>

                <div>
                    <div class="hidden xl:block">
                        <div
                            class="grid grid-cols-12 gap-4 p-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800"
                        >
                            <p class="col-span-2">
                                ID <tip description="Internal ID of the participant entry."></tip> <br>
                                Comp ID <tip description="Competition ID."></tip> <br>
                                Call ID <tip description="The unique call id from the shout switch."></tip>
                            </p>

                            <p class="col-span-2 text-center">
                                Competition Telephone
                                <tip description="The competition phone number."></tip>
                            </p>

                            <p class="col-span-2 text-center">
                                Caller Telephone
                                <tip description="The caller phone number."></tip>
                            </p>

                            <p class="col-span-3 text-center">
                                Call Start
                                <tip description="The time the call started."></tip>
                            </p>

                            <p class="col-span-3 text-center">
                                Organisation
                                <tip description="The owner of the competition."></tip>
                            </p>
                        </div>
                    </div>

                    <template v-for="(participant, index) in props.participants.data.data">
                        <ParticipantDetails :participant="participant" :even="!!(index % 2)"
                                         :index="index"></ParticipantDetails>
                    </template>
                </div>

                <Pagination :data="props.participants"></Pagination>
            </template>

            <p v-else class="bg-gray-100 p-4 mt-4 text-center rounded text-gray-500 text-sm">There are no entries to display.</p>
        </LayoutBox>
    </AuthenticatedLayout>
</template>
