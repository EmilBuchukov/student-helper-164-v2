<script setup>
import UiPagination from '@/Components/ui/Pagination.vue';

defineProps({ links: { type: Array, required: true } });
</script>

<template>
    <UiPagination :links="links" />
</template>
