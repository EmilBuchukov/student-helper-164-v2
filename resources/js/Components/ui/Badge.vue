<script setup>
import { cn } from '@/lib/utils';
import { badgeVariants } from './variants';

const props = defineProps({
    tone: { type: String, default: 'neutral' },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <span :class="cn(badgeVariants({ tone }), props.class)"><slot /></span>
</template>
