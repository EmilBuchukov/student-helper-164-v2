<script setup>
import { buttonVariants } from '@/Components/ui/variants';

defineProps({ type: { type: String, default: 'button' } });
</script>

<template>
    <button :type="type" :class="buttonVariants({ variant: 'secondary' })">
        <slot />
    </button>
</template>
