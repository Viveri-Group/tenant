<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, useForm} from '@inertiajs/vue3';
import Tip from "@/Components/Tip/Tip.vue";
import LayoutBox from "@/Components/Layout/LayoutBox.vue";
import Pagination from "@/Components/Layout/Pagination.vue";
import CompetitionsDetails from "@/Pages/Auth/Competitions/CompetitionsDetails.vue";
import CompetitionsSearch from "@/Pages/Auth/Competitions/CompetitionsSearch.vue";
import Info from "@/Components/Layout/Info.vue";

const props = defineProps({
    competitions: Object,
    defaultSearchFormOptions: Object
});

const form = useForm({
    competition_id: '',
    organisation_id: '',
    call_id:'',
    phone_number: '',
    caller_phone_number: '',
    reason: '',
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
    <Head title="Competitions"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <span class="text-gray-500 font-normal">Competitions</span>
            </h2>
        </template>

        <Info class="mt-4">
            This shows a list of competitions and their current configuration.
        </Info>

        <CompetitionsSearch :default-search-form-options="props.defaultSearchFormOptions"
                        :update-search-criteria="updateSearchCriteria"></CompetitionsSearch>

        <LayoutBox>
            <template v-if="props.competitions.data.data.length > 0">
                <Pagination :data="props.competitions"></Pagination>

                <div>
                    <div class="hidden xl:block">
                        <div
                            class="grid grid-cols-12 gap-4 p-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800"
                        >
                            <p>
                                ID
                                <tip description="Internal ID of the competition."></tip>
                            </p>

                            <p class="col-span-2">
                                Organisation
                                <tip description="Organisation this belongs to."></tip>
                            </p>

                            <p class="col-span-3">
                                Name
                                <tip description="The competition name."></tip>
                            </p>

                            <p class="col-span-3 xl:col-span-3 text-center">
                                Time Details
                                <tip description="The details of when the competition runs from / to."></tip>
                            </p>

                            <p class="text-center">
                                # Entries
                                <tip description="The maximum number of times a person can enter a competition."></tip>
                            </p>

                            <p class="text-center col-span-1">
                                Special Offer
                                <tip description="If a special offer is applied."></tip>
                            </p>
                        </div>
                    </div>

                    <template v-for="(competition, index) in props.competitions.data.data">
                        <CompetitionsDetails :competition="competition" :even="!!(index % 2)"
                                         :index="index"></CompetitionsDetails>
                    </template>
                </div>

                <Pagination :data="props.competitions"></Pagination>
            </template>

            <p v-else class="bg-gray-100 p-4 mt-4 text-center rounded text-gray-500 text-sm">There are no entries to display.</p>
        </LayoutBox>
    </AuthenticatedLayout>
</template>
