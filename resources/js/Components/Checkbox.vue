<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);
const props = defineProps({
    checked: { type: [Array, Boolean], required: true },
    value: { default: null },
});

const proxyChecked = computed({
    get: () => props.checked,
    set: (val) => emit('update:checked', val),
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="size-5 cursor-pointer rounded-sm border-line-strong bg-surface text-primary focus:ring-2 focus:ring-focus/40 focus:ring-offset-0"
    />
</template>
