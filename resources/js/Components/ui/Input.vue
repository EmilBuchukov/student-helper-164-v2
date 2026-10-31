<script setup>
import { onMounted, ref } from 'vue';
import { cn } from '@/lib/utils';
import { fieldClasses } from './variants';

defineOptions({ inheritAttrs: false });
const model = defineModel({ type: [String, Number, null] });
const props = defineProps({
    invalid: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});
const input = ref(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) input.value.focus();
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        ref="input"
        v-model="model"
        v-bind="$attrs"
        :aria-invalid="invalid || undefined"
        :class="cn(fieldClasses, 'h-11', props.class)"
    />
</template>
