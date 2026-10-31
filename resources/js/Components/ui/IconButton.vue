<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { buttonVariants } from './variants';

// Square icon-only button; `label` is required and becomes the accessible name.
const props = defineProps({
    label: { type: String, required: true },
    variant: { type: String, default: 'ghost' },
    size: { type: String, default: 'md' },
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});

const sizes = { sm: 'size-9 px-0', md: 'size-11 px-0', lg: 'size-12 px-0' };
const classes = computed(() => cn(buttonVariants({ variant: props.variant }), sizes[props.size], props.class));
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href ?? undefined"
        :type="href ? undefined : type"
        :disabled="href ? undefined : disabled"
        :class="classes"
        :aria-label="label"
        :title="label"
    >
        <slot />
    </component>
</template>
