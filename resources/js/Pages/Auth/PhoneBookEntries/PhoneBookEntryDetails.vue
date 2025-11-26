<script setup>
import Phone from "@/Mixins/Phone.js"

const {formatNumber} = Phone();

const props = defineProps({
    phoneBookEntry: Object,
    even: Boolean,
    index: Number,
});

</script>

<template>
    <div>
        <div class="border border-gray-100 xl:border-none rounded-lg px-0 text-sm"
             :class="{'mt-4 xl:mt-0' : index > 0}">
            <div class="xl:hidden flex justify-between bg-gray-100 p-2 rounded-t-lg">
                <p class="ml-auto text-gray-600 text-sm" v-text="'ID: ' + props.phoneBookEntry.id"></p>
            </div>

            <div class="grid grid-cols-3 xl:grid-cols-12 gap-y-2 xl:gap-4 px-4 xl:px-2 py-4 xl:py-2"
                 :class="{'xl:bg-gray-100' : even}">

                <div class="hidden xl:flex flex-wrap gap-2">
                    <p class="text-sm" v-text="props.phoneBookEntry.id"></p>
                </div>


                <div class="block xl:hidden font-bold text-gray-400">Name:</div>
                <div class="col-span-2 xl:col-span-3">
                    {{ formatNumber(props.phoneBookEntry.attributes.name)}}
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Organisation:</div>
                <div class="col-span-2 xl:col-span-2">
                    <p class="text-sm">
                        {{props.phoneBookEntry.attributes.organisation_name}}
                        <span class="text-gray-500">({{props.phoneBookEntry.attributes.organisation_id}})</span>
                    </p>
                </div>

                <div class="block xl:hidden font-bold text-gray-400">Phone Number:</div>
                <div class="col-span-2 xl:col-span-2">
                    {{ formatNumber(props.phoneBookEntry.attributes.phone_number)}}
                </div>

                <div class="col-span-3 xl:col-span-4 flex justify-end gap-2">
                    <Link :href="route('web.phone-line-schedule.index', {competitionPhoneNumber: props.phoneBookEntry.attributes.phone_number})" as="button" class="btn btn--sm">
                        Schedule
                    </Link>

                    <Link :href="route('web.phone-book-entries.lookup', {phone_number: props.phoneBookEntry.attributes.phone_number})" as="button" class="btn btn--sm btn--blue">
                        Competition Lookup
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
