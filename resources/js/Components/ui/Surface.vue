<script setup>
import { cn } from '@/lib/utils';

// Basic panel. `as` lets it be an <article>, <section>, <li>…
const props = defineProps({
    as: { type: String, default: 'div' },
    padded: { type: Boolean, default: true },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <component :is="as" :class="cn('rounded border border-line bg-surface', padded && 'p-5 sm:p-6', props.class)">
        <slot />
    </component>
</template>
