<script setup>
import { fieldClasses } from '@/Components/ui/variants';

const model = defineModel({ type: [String, Number], default: '' });
</script>

<template>
    <select v-model="model" :class="[fieldClasses, 'h-11 cursor-pointer pe-9']">
        <slot />
    </select>
</template>
