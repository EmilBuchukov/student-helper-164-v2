<script setup>
import { buttonVariants } from '@/Components/ui/variants';
</script>

<template>
    <button :class="buttonVariants({ variant: 'primary' })">
        <slot />
    </button>
</template>
