<script setup>
import { onMounted, ref } from 'vue';
import { fieldClasses } from '@/Components/ui/variants';

const model = defineModel({ type: [String, Number, null], required: true });
const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input :class="[fieldClasses, 'h-11']" v-model="model" ref="input" />
</template>
