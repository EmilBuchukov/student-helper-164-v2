<script setup>
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';

// Label + control + hint/error. The slot receives `id`, `invalid` and `describedby` to bind on the control.
const props = defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    id: { type: String, default: null },
    required: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});

const autoId = useId();
const fieldId = computed(() => props.id ?? `field-${autoId}`);
const describedby = computed(() =>
    [props.hint && `${fieldId.value}-hint`, props.error && `${fieldId.value}-error`].filter(Boolean).join(' ') || undefined,
);
</script>

<template>
    <div :class="cn('flex flex-col gap-1.5', props.class)">
        <label v-if="label" :for="fieldId" class="text-sm font-medium text-ink">
            {{ label }}<span v-if="required" class="ms-0.5 text-primary-ink" aria-hidden="true">*</span>
        </label>
        <slot :id="fieldId" :invalid="!!error" :describedby="describedby" />
        <p v-if="hint" :id="`${fieldId}-hint`" class="text-sm text-ink-subtle">{{ hint }}</p>
        <p v-if="error" :id="`${fieldId}-error`" class="text-sm font-medium text-danger" role="alert">{{ error }}</p>
    </div>
</template>
