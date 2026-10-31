<script setup>
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { X } from 'lucide-vue-next';

// Accessible modal (focus trap, Esc, scroll lock via Reka). Bottom sheet on phones, centred on desktop.
const open = defineModel('open', { type: Boolean, default: false });
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    size: { type: String, default: 'md' },
});
const widths = { sm: 'sm:max-w-sm', md: 'sm:max-w-lg', lg: 'sm:max-w-2xl' };
</script>

<template>
    <DialogRoot v-model:open="open">
        <slot name="trigger" />
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-sand-950/50 backdrop-blur-[2px] data-[state=open]:animate-fade-in" />
            <DialogContent
                :class="[
                    'fixed inset-x-0 bottom-0 z-50 max-h-[90dvh] overflow-y-auto rounded-t-xl border border-line bg-surface p-5 shadow-pop focus:outline-none data-[state=open]:animate-pop-in sm:inset-x-auto sm:bottom-auto sm:left-1/2 sm:top-1/2 sm:w-[calc(100%-2rem)] sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-lg sm:p-6',
                    widths[size],
                ]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <DialogTitle class="font-display text-title-3 font-semibold text-ink">{{ title }}</DialogTitle>
                        <DialogDescription v-if="description" class="mt-1 text-ink-muted">{{ description }}</DialogDescription>
                    </div>
                    <DialogClose class="-me-2 -mt-1 inline-flex size-11 shrink-0 items-center justify-center rounded text-ink-muted hover:bg-sunken hover:text-ink" :aria-label="$t('a11y.close')">
                        <X class="size-5" aria-hidden="true" />
                    </DialogClose>
                </div>
                <div class="mt-5"><slot /></div>
                <div v-if="$slots.footer" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><slot name="footer" /></div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
