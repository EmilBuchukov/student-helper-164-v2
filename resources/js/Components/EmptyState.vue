<script setup>
import UiEmptyState from '@/Components/ui/EmptyState.vue';

defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
});
</script>

<template>
    <UiEmptyState :title="title" :hint="hint"><slot v-if="$slots.default" /></UiEmptyState>
</template>
