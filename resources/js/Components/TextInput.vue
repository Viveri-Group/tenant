<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="border min-h-9 focus:border-indigo-200 focus:ring-indigo-200 rounded-md shadow-sm px-3 py-2"
        v-model="model"
        ref="input"
    />
</template>
