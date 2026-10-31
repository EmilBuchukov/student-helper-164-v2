<script setup>
import { computed } from 'vue';

// Calendar-leaf date: big day numeral + month abbreviation (bg-BG).
const props = defineProps({ date: { type: String, required: true }, size: { type: String, default: 'md' } });
const parsed = computed(() => (/^\d{4}-\d{2}-\d{2}$/.test(props.date) ? new Date(`${props.date}T00:00:00`) : new Date(props.date)));
const day = computed(() => parsed.value.getDate());
const month = computed(() => parsed.value.toLocaleDateString('bg-BG', { month: 'short' }).replace('.', ''));
const weekday = computed(() => parsed.value.toLocaleDateString('bg-BG', { weekday: 'short' }));
</script>

<template>
    <span :class="['flex shrink-0 flex-col items-center justify-center rounded border border-line bg-canvas text-center', size === 'lg' ? 'w-20 py-2.5' : 'w-16 py-2']">
        <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.1em] text-primary-ink">{{ month }}</span>
        <span :class="['font-display font-semibold leading-none tabular-nums text-ink', size === 'lg' ? 'text-[2.5rem]' : 'text-[2rem]']">{{ day }}</span>
        <span class="mt-0.5 text-[0.6875rem] text-ink-subtle">{{ weekday }}</span>
    </span>
</template>
