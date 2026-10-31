import { reactive } from 'vue';

export const toasts = reactive([]);
let nextId = 1;

// toast('Запазено.') or toast({ message, tone: 'success' | 'danger' | 'info' })
export function toast(input) {
    const item = typeof input === 'string' ? { message: input } : input;
    toasts.push({ id: nextId++, tone: 'success', duration: 5000, ...item, open: true });
}

export function dismissToast(id) {
    const index = toasts.findIndex((item) => item.id === id);
    if (index !== -1) toasts.splice(index, 1);
}
