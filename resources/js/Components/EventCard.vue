<script setup>
import { Link } from '@inertiajs/vue3';
import { Check, MapPin } from 'lucide-vue-next';
import DateBlock from '@/Components/ui/DateBlock.vue';
import { formatDateTime } from '@/format';

defineProps({ event: { type: Object, required: true } });
</script>

<template>
    <Link :href="route('events.show', event.id)" class="group flex gap-4 rounded border border-line bg-surface p-4 transition duration-200 hover:border-line-strong hover:shadow-hairline">
        <DateBlock :date="event.starts_at" />
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-primary-ink">{{ event.club ? event.club.name : $t('events.school_wide') }}</p>
            <p class="mt-1 break-words font-display text-lg font-semibold leading-snug text-ink group-hover:underline group-hover:decoration-accent group-hover:decoration-2 group-hover:underline-offset-4">{{ event.title }}</p>
            <p class="mt-1 flex flex-wrap items-center gap-x-2 text-sm text-ink-muted">
                <span>{{ formatDateTime(event.starts_at) }}</span>
                <span v-if="event.location" class="inline-flex items-center gap-1"><MapPin class="size-3.5" aria-hidden="true" />{{ event.location }}</span>
            </p>
            <p class="mt-2 flex flex-wrap gap-x-3 text-xs text-ink-subtle">
                <span>{{ $t('events.going_count', { count: event.going_count }) }}</span>
                <span>{{ $t('events.interested_count', { count: event.interested_count }) }}</span>
                <span v-if="event.my_rsvp" class="inline-flex items-center gap-1 font-medium text-success"><Check class="size-3.5" aria-hidden="true" />{{ $t(`events.rsvp.${event.my_rsvp}`) }}</span>
            </p>
        </div>
    </Link>
</template>
