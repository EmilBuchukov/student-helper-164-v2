<script setup>
import { Link } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { buttonVariants } from './variants';

const props = defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    block: { type: Boolean, default: false },
    href: { type: String, default: null },
    method: { type: String, default: null },
    external: { type: Boolean, default: false },
    type: { type: String, default: 'button' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});

const classes = computed(() => cn(buttonVariants({ variant: props.variant, size: props.size, block: props.block }), props.class));
const tag = computed(() => {
    if (!props.href) return 'button';
    return props.external ? 'a' : Link;
});
</script>

<template>
    <component
        :is="tag"
        :class="classes"
        :href="href ?? undefined"
        :method="href && method ? method : undefined"
        :as="href && method && method !== 'get' ? 'button' : undefined"
        :type="!href ? type : undefined"
        :disabled="!href ? disabled || loading : undefined"
        :aria-busy="loading || undefined"
    >
        <Loader2 v-if="loading" class="animate-spin" aria-hidden="true" />
        <slot />
    </component>
</template>
