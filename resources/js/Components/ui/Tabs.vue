<script setup>
import { TabsContent, TabsIndicator, TabsList, TabsRoot, TabsTrigger } from 'reka-ui';

// Content tabs with an animated underline. items: [{ value, label }]; content via #<value> slots.
const model = defineModel({ type: String });
defineProps({ items: { type: Array, required: true }, label: { type: String, default: '' } });
</script>

<template>
    <TabsRoot v-model="model">
        <TabsList :aria-label="label" class="relative flex gap-1 overflow-x-auto border-b border-line">
            <TabsTrigger
                v-for="item in items"
                :key="item.value"
                :value="item.value"
                class="min-h-11 whitespace-nowrap px-3 text-sm font-medium text-ink-muted transition-colors hover:text-ink data-[state=active]:text-ink"
            >
                {{ item.label }}
            </TabsTrigger>
            <TabsIndicator class="absolute bottom-0 left-0 h-0.5 w-[--reka-tabs-indicator-size] translate-x-[--reka-tabs-indicator-position] bg-primary transition-[width,transform] duration-200 ease-out" />
        </TabsList>
        <TabsContent v-for="item in items" :key="item.value" :value="item.value" class="pt-5 focus-visible:outline-none">
            <slot :name="item.value" />
        </TabsContent>
    </TabsRoot>
</template>
