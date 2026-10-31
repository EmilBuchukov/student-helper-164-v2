<script setup>
import { AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogOverlay, AlertDialogPortal, AlertDialogRoot, AlertDialogTitle } from 'reka-ui';
import { cn } from '@/lib/utils';
import { confirmState, settleConfirm } from './confirm';
import { buttonVariants } from './variants';
</script>

<template>
    <AlertDialogRoot :open="confirmState.open" @update:open="(value) => !value && settleConfirm(false)">
        <AlertDialogPortal>
            <AlertDialogOverlay class="fixed inset-0 z-50 bg-sand-950/50 backdrop-blur-[2px] data-[state=open]:animate-fade-in" />
            <AlertDialogContent class="fixed inset-x-4 bottom-4 z-50 rounded-lg border border-line bg-surface p-6 shadow-pop focus:outline-none data-[state=open]:animate-pop-in sm:inset-x-auto sm:bottom-auto sm:left-1/2 sm:top-1/2 sm:w-full sm:max-w-md sm:-translate-x-1/2 sm:-translate-y-1/2">
                <AlertDialogTitle class="font-display text-title-3 font-semibold text-ink">{{ $t('confirm.title') }}</AlertDialogTitle>
                <AlertDialogDescription class="mt-2 text-ink-muted">{{ confirmState.message }}</AlertDialogDescription>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <AlertDialogCancel :class="buttonVariants({ variant: 'secondary' })">{{ $t('common.cancel') }}</AlertDialogCancel>
                    <button
                        type="button"
                        :class="cn(buttonVariants({ variant: 'primary' }), confirmState.danger && 'bg-danger text-white hover:bg-danger/90 dark:text-sand-950')"
                        @click="settleConfirm(true)"
                    >
                        {{ confirmState.confirmLabel || (confirmState.danger ? $t('confirm.delete') : $t('confirm.confirm')) }}
                    </button>
                </div>
            </AlertDialogContent>
        </AlertDialogPortal>
    </AlertDialogRoot>
</template>
