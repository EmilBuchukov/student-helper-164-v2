<script setup>
import { cn } from '@/lib/utils';
import { fieldClasses } from './variants';

defineOptions({ inheritAttrs: false });
const model = defineModel({ type: [String, null] });
const props = defineProps({
    invalid: { type: Boolean, default: false },
    rows: { type: [Number, String], default: 5 },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <textarea
        v-model="model"
        v-bind="$attrs"
        :rows="rows"
        :aria-invalid="invalid || undefined"
        :class="cn(fieldClasses, 'min-h-24 py-2.5 leading-relaxed', props.class)"
    />
</template>
