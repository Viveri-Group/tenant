<script setup>
const props = defineProps({
    organisation: Object,
    even: Boolean,
    index: Number,
});

</script>

<template>
    <div>
        <div class="border border-gray-100 xl:border-none rounded-lg px-0 text-sm"
             :class="{'mt-4 xl:mt-0' : index > 0}">
            <div class="xl:hidden flex justify-between bg-gray-100 p-2 rounded-t-lg">
                <p class="text-sm">
                    {{ props.organisation.name }}
                </p>
                <p class="ml-auto text-gray-600 text-sm flex-shrink-0" v-text="'ID: ' + props.organisation.id"></p>
            </div>

            <div class="grid grid-cols-3 xl:grid-cols-12 gap-y-2 xl:gap-4 px-4 xl:px-2 py-4 xl:py-2"
                 :class="{'xl:bg-gray-50' : even}">

                <div class="hidden xl:flex flex-wrap gap-2">
                    <p class="text-sm " v-text="props.organisation.id"></p>
                </div>

                <div class="hidden xl:hidden font-bold text-gray-400">Name:</div>
                <div class="hidden xl:block col-span-2 xl:col-span-3">
                    <p class="text-sm" v-text="props.organisation.name"></p>
                </div>
            </div>
        </div>
    </div>
</template>
