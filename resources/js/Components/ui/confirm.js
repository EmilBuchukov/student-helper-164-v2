import { reactive } from 'vue';

// Promise-based replacement for window.confirm(), rendered by <ConfirmHost /> in the layouts.
export const confirmState = reactive({ open: false, message: '', danger: true, confirmLabel: '', resolve: null });

export function confirmDialog(message, { danger = true, confirmLabel = '' } = {}) {
    confirmState.resolve?.(false);
    return new Promise((resolve) => {
        Object.assign(confirmState, { open: true, message, danger, confirmLabel, resolve });
    });
}

export function settleConfirm(result) {
    const resolve = confirmState.resolve;
    Object.assign(confirmState, { open: false, resolve: null });
    resolve?.(result);
}
