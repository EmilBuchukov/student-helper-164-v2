<script setup>
import { cn } from '@/lib/utils';

// Considered empty state: a small line illustration (open book with a gold bookmark) + copy + action slot.
const props = defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
    compact: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <div :class="cn('flex flex-col items-center rounded border border-dashed border-line-strong px-6 text-center', compact ? 'py-8' : 'py-12 sm:py-16', props.class)">
        <svg v-if="!compact" viewBox="0 0 120 80" class="mb-5 h-16 w-24 text-ink-subtle" aria-hidden="true" fill="none">
            <path d="M60 18c-12-8-30-9-44-5v52c14-4 32-3 44 5 12-8 30-9 44-5V13c-14-4-32-3-44 5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
            <path d="M60 18v52" stroke="currentColor" stroke-width="2" />
            <path d="M26 28h22M26 37h22M26 46h16M72 28h22M72 37h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".45" />
            <path d="M84 10v22l5-4 5 4V10" fill="rgb(var(--accent))" />
            <circle cx="60" cy="10" r="3" fill="rgb(var(--primary))" />
        </svg>
        <p class="font-display text-title-3 font-semibold text-ink">{{ title }}</p>
        <p v-if="hint" class="mt-2 max-w-[48ch] text-ink-muted">{{ hint }}</p>
        <div v-if="$slots.default" class="mt-5 flex flex-wrap justify-center gap-2"><slot /></div>
    </div>
</template>
