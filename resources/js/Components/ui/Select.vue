<script setup>
import { cn } from '@/lib/utils';
import { fieldClasses } from './variants';

// Native <select> for best mobile UX; options are passed as children.
defineOptions({ inheritAttrs: false });
const model = defineModel({ type: [String, Number, null] });
const props = defineProps({
    invalid: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <select
        v-model="model"
        v-bind="$attrs"
        :aria-invalid="invalid || undefined"
        :class="cn(fieldClasses, 'h-11 cursor-pointer pe-9', props.class)"
    >
        <slot />
    </select>
</template>
