<script setup>
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const success = computed(() => usePage().props.flash?.success);
const error = computed(() => usePage().props.flash?.error);
</script>

<template>
    <div v-if="success" class="flex items-start gap-3 rounded border border-success/30 bg-success-soft px-4 py-3 text-sm text-success" role="status">
        <CheckCircle2 class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ success }}
    </div>
    <div v-if="error" class="flex items-start gap-3 rounded border border-danger/30 bg-danger-soft px-4 py-3 text-sm text-danger" role="alert">
        <AlertCircle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ error }}
    </div>
</template>
