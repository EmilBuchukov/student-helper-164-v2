<script setup>
import Wordmark from '@/Components/ui/Wordmark.vue';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="mt-16 border-t border-line">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-10 sm:px-6 md:flex-row md:items-end md:justify-between lg:px-8">
            <div class="max-w-md">
                <Wordmark compact />
                <p class="mt-3 text-sm leading-relaxed text-ink-muted">{{ $t('footer.about') }}</p>
            </div>
            <p class="text-sm text-ink-subtle">{{ $t('footer.rights', { year }) }}</p>
        </div>
    </footer>
</template>
