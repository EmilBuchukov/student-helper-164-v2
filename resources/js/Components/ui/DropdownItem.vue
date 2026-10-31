<script setup>
import { Link } from '@inertiajs/vue3';
import { DropdownMenuItem } from 'reka-ui';

defineProps({
    href: { type: String, default: null },
    method: { type: String, default: null },
    danger: { type: Boolean, default: false },
});
defineEmits(['select']);
</script>

<template>
    <DropdownMenuItem as-child @select="$emit('select', $event)">
        <component
            :is="href ? Link : 'button'"
            :href="href ?? undefined"
            :method="method ?? undefined"
            :as="method && method !== 'get' ? 'button' : undefined"
            :type="href ? undefined : 'button'"
            :class="[
                'flex min-h-11 w-full cursor-pointer select-none items-center gap-3 rounded px-3 text-left text-[0.9375rem] outline-none transition-colors data-[highlighted]:bg-sunken [&_svg]:size-4 [&_svg]:text-ink-subtle',
                danger ? 'text-danger' : 'text-ink',
            ]"
        >
            <slot />
        </component>
    </DropdownMenuItem>
</template>
