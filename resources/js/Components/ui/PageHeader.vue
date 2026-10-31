<script setup>
import Kicker from './Kicker.vue';

// Editorial page header: kicker, serif title, lead, actions aligned to the baseline on desktop.
defineProps({
    title: { type: String, required: true },
    kicker: { type: String, default: '' },
    lead: { type: String, default: '' },
});
</script>

<template>
    <header class="flex flex-col gap-5 border-b border-line pb-6 sm:pb-8 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 max-w-3xl">
            <slot name="before" />
            <Kicker v-if="kicker">{{ kicker }}</Kicker>
            <h1 class="mt-2 font-display text-title-1 font-semibold text-ink">{{ title }}</h1>
            <p v-if="lead" class="mt-3 max-w-[60ch] text-lg leading-relaxed text-ink-muted">{{ lead }}</p>
            <slot name="meta" />
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </header>
</template>
