<script setup>
import { formatDateTime } from '@/format';

defineProps({
    logs: { type: Array, required: true },
});

const detailText = (details) =>
    Object.entries(details)
        .filter(([, v]) => v !== null && v !== false && v !== '')
        .map(([k, v]) => `${k}: ${v === true ? '✓' : v}`)
        .join(' · ');
</script>

<template>
    <div>
        <p v-if="logs.length === 0" class="text-sm text-ink-subtle">{{ $t('admin.audit.empty') }}</p>
        <ul v-else class="divide-y divide-line border-y border-line">
            <li v-for="log in logs" :key="log.id" class="py-3 text-sm">
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <span class="font-medium text-ink">{{ log.actor ?? $t('admin.audit.system') }}</span>
                    <span class="rounded bg-sunken px-1.5 py-0.5 text-xs text-ink-muted">{{ $t(`admin.audit.actions.${log.action}`) }}</span>
                    <span v-if="log.label" class="break-words text-ink-muted">„{{ log.label }}“</span>
                    <span class="ms-auto text-xs text-ink-subtle">{{ formatDateTime(log.created_at) }}</span>
                </div>
                <p v-if="detailText(log.details)" class="mt-0.5 break-words text-xs text-ink-subtle">{{ detailText(log.details) }}</p>
            </li>
        </ul>
    </div>
</template>
