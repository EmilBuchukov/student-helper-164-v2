import { ref } from 'vue';

// Theme preference: 'light' | 'dark' | 'system' (default). Applied as `.dark` on <html>.
const STORAGE_KEY = 'theme';
const media = typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: dark)') : null;

function read() {
    try {
        return localStorage.getItem(STORAGE_KEY) || 'system';
    } catch {
        return 'system';
    }
}

export const theme = ref(typeof window !== 'undefined' ? read() : 'system');

function apply() {
    const dark = theme.value === 'dark' || (theme.value === 'system' && media?.matches);
    document.documentElement.classList.toggle('dark', !!dark);
}

export function setTheme(value) {
    theme.value = value;
    try {
        localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // Storage unavailable (private mode): keep the in-memory preference.
    }
    apply();
}

media?.addEventListener('change', () => theme.value === 'system' && apply());
