<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

// Renders Laravel paginator links. First/last entries are prev/next; labels come from the paginator.
const props = defineProps({ links: { type: Array, required: true } });

const prev = computed(() => props.links[0]);
const next = computed(() => props.links[props.links.length - 1]);
const pages = computed(() => props.links.slice(1, -1));
const item = 'inline-flex h-11 min-w-11 items-center justify-center rounded px-3 text-sm font-medium tabular-nums transition';
</script>

<template>
    <nav v-if="links.length > 3" :aria-label="$t('a11y.pagination')" class="flex items-center justify-between gap-2 border-t border-line pt-4 sm:justify-center">
        <component
            :is="prev.url ? Link : 'span'"
            :href="prev.url ?? undefined"
            preserve-scroll
            :class="[item, prev.url ? 'text-ink hover:bg-sunken' : 'text-ink-subtle opacity-60']"
            :aria-label="$t('a11y.previous')"
        >
            <ChevronLeft class="size-5" aria-hidden="true" />
        </component>
        <ul class="hidden items-center gap-1 sm:flex">
            <li v-for="(link, index) in pages" :key="index">
                <span v-if="!link.url" :class="[item, 'text-ink-subtle']" v-html="link.label" />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    :class="[item, link.active ? 'bg-ink text-canvas' : 'text-ink-muted hover:bg-sunken hover:text-ink']"
                    :aria-current="link.active ? 'page' : undefined"
                    v-html="link.label"
                />
            </li>
        </ul>
        <span class="text-sm tabular-nums text-ink-muted sm:hidden">
            {{ pages.find((link) => link.active)?.label }} / {{ pages[pages.length - 1]?.label }}
        </span>
        <component
            :is="next.url ? Link : 'span'"
            :href="next.url ?? undefined"
            preserve-scroll
            :class="[item, next.url ? 'text-ink hover:bg-sunken' : 'text-ink-subtle opacity-60']"
            :aria-label="$t('a11y.next')"
        >
            <ChevronRight class="size-5" aria-hidden="true" />
        </component>
    </nav>
</template>
