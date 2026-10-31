<script setup>
import { Link } from '@inertiajs/vue3';

// Link-based filter tabs (e.g. upcoming/past) with the same underline language as Tabs.
defineProps({
    items: { type: Array, required: true }, // [{ label, href, active }]
    label: { type: String, default: '' },
});
</script>

<template>
    <nav :aria-label="label" class="flex gap-1 overflow-x-auto border-b border-line">
        <Link
            v-for="item in items"
            :key="item.href"
            :href="item.href"
            preserve-scroll
            :aria-current="item.active ? 'page' : undefined"
            :class="[
                'relative inline-flex min-h-11 items-center whitespace-nowrap px-3 text-sm font-medium transition-colors after:absolute after:inset-x-2 after:bottom-0 after:h-0.5 after:origin-left after:bg-primary after:transition-transform after:duration-200',
                item.active ? 'text-ink after:scale-x-100' : 'text-ink-muted after:scale-x-0 hover:text-ink',
            ]"
        >
            {{ item.label }}
        </Link>
    </nav>
</template>
