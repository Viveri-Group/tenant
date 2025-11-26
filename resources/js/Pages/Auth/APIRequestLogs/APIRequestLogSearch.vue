<script setup>
import Input from "@/Components/Form/Input.vue";
import SearchContainer from "@/Components/Layout/SearchContainer.vue";
import LayoutBox from "@/Components/Layout/LayoutBox.vue";
import Select from "@/Components/Form/Select.vue"
import {useForm} from "@inertiajs/vue3";
import {onMounted} from "vue";
import OrderBy from "@/Components/Form/OrderBy.vue";

const props = defineProps({
    defaultSearchFormOptions: Object,
    updateSearchCriteria: Function,
});

const form = useForm({
    uuid: '',
    user_id: '',
    request_type:'',
    request_headers:'',
    request_input: '',
    request_output: '',
    response_status: '',
    duration: '',
    call_id: '',
    caller_number: '',
    competition_phone_number: '',
    response_data: '',
    date_to: '',
    date_from: '',
    order_by: '',
    order_by_direction: '',
});

const submit = () => {
    props.updateSearchCriteria(form.data());

    form.get(route('web.api-request-logs.index'), {
        preserveScroll: true
    });
};

onMounted(() => {
    (new URLSearchParams(window.location.search)).forEach((value, key) => {
        form[key] = value;
    });

    props.updateSearchCriteria(form.data());
});

const clearForm = () => {
    form.reset()
    form.date_from = props.defaultSearchFormOptions.date_from;
    form.date_to = props.defaultSearchFormOptions.date_to;
    submit();
};
</script>

<template>
    <LayoutBox>
        <SearchContainer heading="Search API Request Logs" storage-key="hide_api_request_log_search_panel">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-12 gap-x-4 gap-y-1">
<!--                    <Input label="UUID"-->
<!--                           name="uuid"-->
<!--                           tip="The ID of the competition."-->
<!--                           v-model="form.uuid"-->
<!--                           class="col-span-12 sm:col-span-6 xl:col-span-4"-->
<!--                    ></Input>-->

<!--                    <Select label="User"-->
<!--                            name="user_id"-->
<!--                            tip="The user who made the request."-->
<!--                            :has-default="true"-->
<!--                            :options="props.defaultSearchFormOptions.users"-->
<!--                            v-model="form.user_id"-->
<!--                            class="col-span-12 sm:col-span-6 xl:col-span-3"-->
<!--                    ></Select>-->

                    <Input label="Request Type"
                           name="request_type"
                           tip="The request type."
                           v-model="form.request_type"
                           class="col-span-12 sm:col-span-4 xl:col-span-4"
                    ></Input>

<!--                    <Input label="Request Input"-->
<!--                           name="request_input"-->
<!--                           tip="Information passed to the request."-->
<!--                           v-model="form.request_input"-->
<!--                           class="col-span-12 sm:col-span-4 xl:col-span-4"-->
<!--                    ></Input>-->

<!--                    <Input label="Request Output"-->
<!--                           name="request_output"-->
<!--                           tip="Information returned from the request."-->
<!--                           v-model="form.request_output"-->
<!--                           class="col-span-12 sm:col-span-4 xl:col-span-4"-->
<!--                    ></Input>-->

                    <Input label="Call ID"
                           name="call_id"
                           tip="The relating Shout Call ID if applicable."
                           v-model="form.call_id"
                           class="col-span-12 sm:col-span-4 xl:col-span-2"
                    ></Input>

                    <Input label="Caller Number"
                           name="caller_number"
                           tip="The relating Caller Phone Number if applicable."
                           v-model="form.caller_number"
                           class="col-span-12 sm:col-span-4 xl:col-span-3"
                    ></Input>

                    <Input label="Competition Number"
                           name="competition_phone_number"
                           tip="The relating Competition Number if applicable."
                           v-model="form.competition_phone_number"
                           class="col-span-12 sm:col-span-4 xl:col-span-3"
                    ></Input>

<!--                    <Input label="HTTP Response"-->
<!--                           name="response_status"-->
<!--                           tip="The http response status code."-->
<!--                           v-model="form.response_status"-->
<!--                           class="col-span-12 sm:col-span-6 xl:col-span-3"-->
<!--                    ></Input>-->

                    <Select label="Request Duration"
                            name="duration"
                            tip="Time took to complete the request."
                            :has-default="true"
                            :options="[{label:'0ms - 500ms', value: '1'},{label:'501ms - 1000ms', value: '2'},{label:'1000ms and greater', value: '3'}]"
                            v-model="form.duration"
                            class="col-span-12 sm:col-span-6 xl:col-span-4"
                    ></Select>

                    <Input label="Date From"
                           type="datetime-local"
                           name="date-from"
                           tip="Date from range."
                           v-model="form.date_from"
                           class="col-span-6 lg:col-span-6 xl:col-span-4"
                    ></Input>

                    <Input label="Date To"
                           type="datetime-local"
                           name="date-to"
                           tip="Date to range."
                           v-model="form.date_to"
                           class="col-span-6 lg:col-span-6 xl:col-span-4"
                    ></Input>

                    <div class="col-span-12">
                        <div class="flex justify-between items-center">
                            <template v-if="props.defaultSearchFormOptions.orderBy">
                                <OrderBy
                                    :order-by-config="props.defaultSearchFormOptions.orderBy"
                                    :form="form"
                                ></OrderBy>
                            </template>

                            <div v-else></div>

                            <div class="flex-col xl:flex-row flex justify-between gap-2">
                                <button class="btn btn--gray" type="reset" @click.prevent="clearForm">Clear</button>
                                <input type="submit" value="Submit" class="btn btn--blue ml-auto inline-block"></input>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </SearchContainer>
    </LayoutBox>
</template>
