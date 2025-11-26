<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, router, useForm} from '@inertiajs/vue3';
import Tip from "@/Components/Tip/Tip.vue";
import LayoutBox from "@/Components/Layout/LayoutBox.vue";
import Pagination from "@/Components/Layout/Pagination.vue";
import ActiveCallDetails from "@/Pages/Auth/ActiveCalls/ActiveCallDetails.vue";
import ActiveCallsSearch from "@/Pages/Auth/ActiveCalls/ActiveCallsSearch.vue";
import CountDownTimer from "@/Components/Timer/CountDownTimer.vue";
import Info from "@/Components/Layout/Info.vue";
import {computed, ref, watch} from "vue";
import {format} from "date-fns";
import LineChart from "@/Components/Charts/LineChart.vue";
import RecordButton from "@/Components/Buttons/RecordButton.vue";

const props = defineProps({
    activeCalls: Object,
    defaultSearchFormOptions: Object,
    enableMaxLines: Boolean,
    maxActiveLines: String
});

const interval = 30;
const captureGraphData = ref(false);
let graphDataPoints = ref([]);
let graphDataLabels = [];
let graphPointsTitle = '';

watch(() => props.activeCalls, (newVal, oldVal) => {
    if (captureGraphData.value) {
        if (captureGraphData.value) {
            makeGraphDataEntry();
        }
    }
});

const chartData = computed(() => ({
    labels: graphDataLabels,
    datasets: [{
        data: graphDataPoints.value,
        label: graphPointsTitle,
        fill: true,
        borderColor: '#649cea',
    }]
}));

const form = useForm({
    competition_id: '',
    call_id: '',
    phone_number: '',
    caller_phone_number: '',
    date_from: '',
    date_to: '',
});

const toggleCaptureGraphData = () => {
    captureGraphData.value = !captureGraphData.value;

    if (!captureGraphData.value) {
        graphDataLabels = [];
        graphDataPoints.value = [];
    }

    if (captureGraphData.value) {
        graphPointsTitle = `Active Calls Monitor - ${format(new Date(), "do MMM yyyy HH:mm:ss aa")}`;
        makeGraphDataEntry();
    }
};

const updateSearchCriteria = (searchParams) => {
    Object.entries(searchParams).forEach(([key, value]) => {
        form[key] = value
    });
};

const makeGraphDataEntry = () => {
    graphDataLabels.push(format(new Date(), 'HH:mm:ss'));

    const data = graphDataPoints.value;
    graphDataPoints.value = [...data, props.activeCalls.total]
}

const beautifyNumbers = () => {
    if(props.maxActiveLines < 0) {
        return 'N/A';
    }

    const maxLines = props.maxActiveLines;

    return maxLines.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

const handleAutoReload = () => {
    router.reload({
        only: ['activeCalls'],
    });
};
</script>

<template>
    <Head title="Active Calls"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <span class="text-gray-500 font-normal">Active Calls</span>
            </h2>
        </template>

        <Info class="mt-4">
            Active Calls shows live call data. Successful calls are expected to complete within 1 minute.
        </Info>

        <ActiveCallsSearch
            :default-search-form-options="props.defaultSearchFormOptions"
            :update-search-criteria="updateSearchCriteria">
        </ActiveCallsSearch>

        <LayoutBox>
            <div class="flex justify-between items-center">
                <div class="flex gap-2">
                    <div class="border rounded-lg p-2 text-center">
                        <p class="text-xs text-gray-400">Current Total</p>
                        <p class="text-xl" v-text="props.activeCalls.total"></p>
                    </div>

                    <template v-if="props.enableMaxLines">
                        <div class="border rounded-lg p-2 text-center">
                            <p class="text-xs text-gray-400">Max Lines</p>
                            <p class="text-xl" v-text="beautifyNumbers()"></p>
                        </div>
                    </template>
                </div>

                <div class="flex justify-between gap-4 items-center">
                    <RecordButton
                        :record="captureGraphData"
                        :handle-click="toggleCaptureGraphData">
                    </RecordButton>

                    <div class="flex justify-end">
                        <CountDownTimer
                            :count-down-value="interval"
                            :auto-start="false"
                            :auto-restart="true"
                            :final-stop-after="60 * interval"
                            turn-amber-at="10"
                            turn-red-at="5"
                            @count-down-ended="handleAutoReload"
                        >
                        </CountDownTimer>
                        <tip class="hidden lg:inline-block" description="Automatically reload page data."></tip>
                    </div>
                </div>
            </div>
        </LayoutBox>

        <LayoutBox v-if="captureGraphData">
            <div class="flex-grow overflow-hidden">
                <LineChart
                    :key="graphDataPoints.length"
                    :data="chartData"
                    :options="{ responsive: true, maintainAspectRatio: false }">
                </LineChart>
            </div>
        </LayoutBox>

        <LayoutBox>
            <template v-if="props.activeCalls.data.data.length > 0">
                <Pagination :data="props.activeCalls"></Pagination>

                <div>
                    <div class="hidden xl:block">
                        <div
                            class="grid grid-cols-12 gap-4 p-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800"
                        >
                            <p class="col-span-2">
                                ID
                                <tip description="Internal ID of the entry."></tip>
                                <br>
                                Comp ID
                                <tip description="Competition ID."></tip>
                                <br>
                                Call ID
                                <tip description="The unique call id from the shout switch."></tip>
                            </p>

                            <p class="col-span-2 text-center">
                                Competition Number
                                <tip description="The competition phone number."></tip>
                            </p>

                            <p class="col-span-2 text-center">
                                Caller Number
                                <tip description="The caller phone number."></tip>
                            </p>

                            <p class="col-span-2 text-center">
                                Organisation
                                <tip description="Owner of the phone line."></tip>
                            </p>

                            <p class="col-span-2 text-center">
                                Status
                                <tip description="Current status of the call."></tip>
                            </p>

                            <p class="col-span-2 text-center">
                                Created
                                <tip description="When the call was received."></tip>
                            </p>
                        </div>
                    </div>

                    <template v-for="(activeCall, index) in props.activeCalls.data.data">
                        <ActiveCallDetails :active-call="activeCall" :even="!!(index % 2)"
                                           :index="index"></ActiveCallDetails>
                    </template>
                </div>

                <Pagination :data="props.activeCalls"></Pagination>
            </template>

            <p v-else class="bg-gray-100 p-4 mt-4 text-center rounded text-gray-500 text-sm">There are no entries to
                display.</p>
        </LayoutBox>
    </AuthenticatedLayout>
</template>
