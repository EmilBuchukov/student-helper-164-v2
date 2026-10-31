<script setup>
import { buttonVariants } from '@/Components/ui/variants';
</script>

<template>
    <button :class="buttonVariants({ variant: 'danger' })">
        <slot />
    </button>
</template>
