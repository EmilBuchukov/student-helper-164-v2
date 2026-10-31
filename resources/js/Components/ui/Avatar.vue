<script setup>
import { computed } from 'vue';
import { cn, initials } from '@/lib/utils';

// Initials avatar; tone is derived from the name so people are recognisable at a glance.
const props = defineProps({
    name: { type: String, default: '' },
    size: { type: String, default: 'md' },
    class: { type: [String, Array, Object], default: '' },
});

const tones = [
    'bg-primary-soft text-primary-ink',
    'bg-accent-soft text-accent-ink',
    'bg-info-soft text-info',
    'bg-success-soft text-success',
    'bg-sunken text-ink-muted',
];
const tone = computed(() => {
    let hash = 0;
    for (const ch of props.name ?? '') hash = (hash * 31 + ch.charCodeAt(0)) >>> 0;
    return tones[hash % tones.length];
});
const sizes = { sm: 'size-8 text-xs', md: 'size-10 text-sm', lg: 'size-14 text-lg' };
</script>

<template>
    <span
        :class="cn('inline-flex shrink-0 select-none items-center justify-center rounded-full font-semibold tracking-wide', sizes[size], tone, props.class)"
        aria-hidden="true"
    >
        {{ initials(name) }}
    </span>
</template>
