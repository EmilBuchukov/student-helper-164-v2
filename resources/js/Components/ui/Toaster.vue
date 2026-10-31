<script setup>
import { ToastClose, ToastDescription, ToastProvider, ToastRoot, ToastViewport } from 'reka-ui';
import { AlertCircle, CheckCircle2, Info, X } from 'lucide-vue-next';
import { dismissToast, toasts } from './toast';

const icons = { success: CheckCircle2, danger: AlertCircle, info: Info };
const tones = { success: 'text-success', danger: 'text-danger', info: 'text-info' };
</script>

<template>
    <ToastProvider :label="$t('a11y.notifications')" swipe-direction="right">
        <ToastRoot
            v-for="item in toasts"
            :key="item.id"
            :duration="item.duration"
            :type="item.tone === 'danger' ? 'foreground' : 'background'"
            class="flex items-start gap-3 rounded-lg border border-line bg-surface p-4 shadow-pop data-[state=closed]:animate-fade-in data-[state=open]:animate-pop-in data-[swipe=move]:translate-x-[--reka-toast-swipe-move-x]"
            @update:open="(open) => !open && dismissToast(item.id)"
        >
            <component :is="icons[item.tone] ?? Info" :class="['mt-0.5 size-5 shrink-0', tones[item.tone]]" aria-hidden="true" />
            <ToastDescription class="flex-1 text-[0.9375rem] text-ink">{{ item.message }}</ToastDescription>
            <ToastClose class="-m-2 inline-flex size-9 items-center justify-center rounded text-ink-subtle hover:bg-sunken hover:text-ink" :aria-label="$t('a11y.close')">
                <X class="size-4" aria-hidden="true" />
            </ToastClose>
        </ToastRoot>
        <ToastViewport class="fixed bottom-0 right-0 z-[60] flex w-full max-w-sm flex-col gap-2 p-4 outline-none sm:bottom-4 sm:right-4 sm:p-0" />
    </ToastProvider>
</template>
