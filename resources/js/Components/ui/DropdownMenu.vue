<script setup>
import { DropdownMenuContent, DropdownMenuPortal, DropdownMenuRoot, DropdownMenuTrigger } from 'reka-ui';

// Menu shell; put DropdownItem components in the default slot and the trigger in #trigger.
defineProps({ align: { type: String, default: 'end' }, width: { type: String, default: 'w-60' } });
</script>

<template>
    <DropdownMenuRoot :modal="false">
        <DropdownMenuTrigger as-child><slot name="trigger" /></DropdownMenuTrigger>
        <DropdownMenuPortal>
            <DropdownMenuContent
                :align="align"
                :side-offset="8"
                :class="['z-50 rounded-lg border border-line bg-surface p-1.5 shadow-pop data-[state=open]:animate-pop-in', width]"
            >
                <slot />
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
