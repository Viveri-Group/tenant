<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import Tip from "@/Components/Tip/Tip.vue";
import LayoutBox from "@/Components/Layout/LayoutBox.vue";
import Info from "@/Components/Layout/Info.vue";
import PhoneBookEntryDetails from "@/Pages/Auth/PhoneBookEntries/PhoneBookEntryDetails.vue";

const props = defineProps({
    phoneBookEntries: Object,
});
</script>

<template>
    <Head title="Phone Book Entries"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <span class="text-gray-500 font-normal">Phone Book Entries</span>
            </h2>
        </template>

        <Info class="mt-4">
            Phone Book Entries are all the phone numbers available within the API.
        </Info>

        <LayoutBox>
            <template v-if="props.phoneBookEntries.data.length > 0">

                <div>
                    <div class="hidden xl:block">
                        <div
                            class="grid grid-cols-12 gap-4 p-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800"
                        >
                            <p>
                                ID
                                <tip description="Internal ID of the entry."></tip>
                            </p>

                            <p class="col-span-3">
                                Name
                                <tip description="Description of the phone line number."></tip>
                            </p>

                            <p class="col-span-2">
                                Organisation
                                <tip description="The owner of this phone line."></tip>
                            </p>

                            <p class="col-span-2">
                                Phone Number
                                <tip description="The competition phone number."></tip>
                            </p>
                        </div>
                    </div>

                    <template v-for="(phoneBookEntry, index) in props.phoneBookEntries.data">
                        <PhoneBookEntryDetails
                            :phone-book-entry="phoneBookEntry" :even="!!(index % 2)"
                            :index="index"
                        ></PhoneBookEntryDetails>
                    </template>
                </div>
            </template>

            <p v-else class="bg-gray-100 p-4 mt-4 text-center rounded text-gray-500 text-sm">There are no entries to display.</p>
        </LayoutBox>
    </AuthenticatedLayout>
</template>
