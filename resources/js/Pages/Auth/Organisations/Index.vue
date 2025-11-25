<script setup>
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Info from "@/Components/Layout/Info.vue";
import LayoutBox from "@/Components/Layout/LayoutBox.vue";
import Tip from "@/Components/Tip/Tip.vue";
import OrganisationsDetails from "@/Pages/Auth/Organisations/OrganisationsDetails.vue";

const props = defineProps({
    organisations: Object,
});
</script>

<template>
    <Head title="Organisations"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <span class="text-gray-500 font-normal">Organisations</span>
            </h2>
        </template>

        <Info class="mt-4">
            A list of organisations that host competitions within this system.
        </Info>

        <LayoutBox>
            <div>
                <div class="hidden xl:block">
                    <div
                        class="grid grid-cols-12 gap-4 p-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800"
                    >
                        <p>
                            ID
                            <tip description="Internal ID of the organisation."></tip>
                        </p>

                        <p class="col-span-3">
                            Name
                            <tip description="The organisation name."></tip>
                        </p>
                    </div>
                </div>

                <template v-for="(organisation, index) in props.organisations.data">
                    <OrganisationsDetails :organisation="organisation" :even="!!(index % 2)"
                                         :index="index"></OrganisationsDetails>
                </template>
            </div>
        </LayoutBox>
    </AuthenticatedLayout>
</template>
