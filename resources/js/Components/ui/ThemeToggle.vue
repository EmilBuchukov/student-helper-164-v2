<script setup>
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { setTheme, theme } from '@/lib/theme';

// Three-way segmented switch: light / system / dark.
const options = [
    { value: 'light', icon: Sun, label: 'theme.light' },
    { value: 'system', icon: Monitor, label: 'theme.system' },
    { value: 'dark', icon: Moon, label: 'theme.dark' },
];
</script>

<template>
    <div role="radiogroup" :aria-label="$t('theme.label')" class="inline-flex rounded-full border border-line bg-sunken/70 p-0.5">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            role="radio"
            :aria-checked="theme === option.value"
            :aria-label="$t(option.label)"
            :title="$t(option.label)"
            :class="[
                'inline-flex size-9 items-center justify-center rounded-full transition duration-150',
                theme === option.value ? 'bg-surface text-ink shadow-hairline' : 'text-ink-subtle hover:text-ink',
            ]"
            @click="setTheme(option.value)"
        >
            <component :is="option.icon" class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
