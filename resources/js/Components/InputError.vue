<script setup>
defineProps({ message: { type: String } });
</script>

<template>
    <div v-show="message">
        <p class="text-sm font-medium text-danger" role="alert">{{ message }}</p>
    </div>
</template>
