<script setup>
import { useId } from 'vue';

// Restrained azulejo-inspired 8-point star tile pattern; decorative only.
defineProps({ tone: { type: String, default: 'gold' } });
const id = `azulejo-${useId()}`;
</script>

<template>
    <svg class="pointer-events-none absolute inset-0 h-full w-full" aria-hidden="true">
        <defs>
            <pattern :id="id" width="56" height="56" patternUnits="userSpaceOnUse">
                <g fill="none" :stroke="tone === 'gold' ? 'rgb(241 191 0)' : 'currentColor'" stroke-width="1.25">
                    <path d="M28 8 34 22 48 28 34 34 28 48 22 34 8 28 22 22Z" />
                    <rect x="18" y="18" width="20" height="20" transform="rotate(45 28 28)" />
                    <circle cx="28" cy="28" r="3" />
                    <path d="M0 0h8M48 0h8M0 56h8M48 56h8M0 0v8M56 0v8M0 48v8M56 48v8" />
                </g>
            </pattern>
        </defs>
        <rect width="100%" height="100%" :fill="`url(#${id})`" />
    </svg>
</template>
