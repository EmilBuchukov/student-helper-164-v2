<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>

<template>
    <div
        :class="cn('relative overflow-hidden rounded bg-sunken before:absolute before:inset-0 before:-translate-x-full before:animate-shimmer before:bg-gradient-to-r before:from-transparent before:via-surface/60 before:to-transparent', props.class)"
        aria-hidden="true"
    />
</template>
