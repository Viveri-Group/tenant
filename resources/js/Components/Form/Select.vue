<template>
    <div>
        <div :class="{ 'flex justify-between': inline }">
            <div class="flex flex-shrink-0 items-center justify-between">
                <div class="flex flex-shrink-0">
                    <label :class="labelStyle" :for="name" v-text="label"></label>
                    <span v-if="required" class="text-red-600"> *</span>
                </div>

                <tip v-if="tip" :description="tip"></tip>
            </div>

            <select
                class="input"
                v-bind="$attrs"
                :value="modelValue"
                @change="$emit('update:modelValue', $event.target.value)"
            >
                <option value="" v-if="hasDefault">Please Select...</option>

                <option
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                    :class="{ hidden: option.hidden }"
                >
                    {{ option.label }}
                </option>
            </select>

        </div>
        <ErrorInput :error-key="errorKey"></ErrorInput>
    </div>
</template>

<script>
import FormField from '@/Mixins/FormField.js';
import ErrorInput from "@/Components/Error/ErrorInput.vue";

export default {
    components: {ErrorInput},
    mixins: [FormField],

    props: {
        options: {
            required: true,
        },

        inline: {
            type: Boolean,
            default: false,
        },

        labelStyle: {
            type: String,
            default: 'text-gray-700 text-sm font-bold',
        },

        hasDefault: {
            default: false,
        },
    },
};
</script>
