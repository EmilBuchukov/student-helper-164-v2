<script setup>
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });
const model = defineModel({ type: [Boolean, Array] });
const props = defineProps({
    value: { type: [String, Number, Boolean], default: null },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <label :class="cn('inline-flex min-h-11 cursor-pointer items-center gap-3 text-[0.9375rem] text-ink', props.class)">
        <input
            v-model="model"
            v-bind="$attrs"
            type="checkbox"
            :value="value"
            class="size-5 shrink-0 cursor-pointer rounded-sm border-line-strong bg-surface text-primary transition focus:ring-2 focus:ring-focus/40 focus:ring-offset-0 focus-visible:outline-none"
        />
        <span v-if="$slots.default"><slot /></span>
    </label>
</template>
