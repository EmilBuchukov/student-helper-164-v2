<script setup>
// School crest + typographic wordmark.
defineProps({ compact: { type: Boolean, default: false } });
</script>

<template>
    <span class="inline-flex items-center gap-2.5">
        <img src="/images/logo.png" alt="" class="size-9 shrink-0 object-contain" width="36" height="36" aria-hidden="true" />
        <span class="flex flex-col leading-none">
            <span class="font-display text-[1.375rem] font-bold tracking-tight text-ink">{{ $t('app.name') }}</span>
            <span v-if="!compact" class="mt-0.5 hidden text-[0.6875rem] font-medium uppercase tracking-[0.12em] text-ink-subtle sm:block">{{ $t('app.patron') }}</span>
        </span>
    </span>
</template>
