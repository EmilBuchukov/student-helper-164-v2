<script setup>
import { TooltipArrow, TooltipContent, TooltipPortal, TooltipProvider, TooltipRoot, TooltipTrigger } from 'reka-ui';

defineProps({ text: { type: String, required: true }, side: { type: String, default: 'top' } });
</script>

<template>
    <TooltipProvider :delay-duration="300">
        <TooltipRoot>
            <TooltipTrigger as-child><slot /></TooltipTrigger>
            <TooltipPortal>
                <TooltipContent :side="side" :side-offset="6" class="z-50 max-w-xs rounded bg-ink px-2.5 py-1.5 text-xs font-medium text-canvas shadow-pop data-[state=delayed-open]:animate-fade-in">
                    {{ text }}
                    <TooltipArrow class="fill-ink" />
                </TooltipContent>
            </TooltipPortal>
        </TooltipRoot>
    </TooltipProvider>
</template>
