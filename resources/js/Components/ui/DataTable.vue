<script setup>
// Dense, readable table. On small screens each row becomes a stacked card (labels via data-label).
// columns: [{ key, label, class? }]; use #cell-<key>="{ row }" slots for custom cells.
defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, required: true },
    rowKey: { type: String, default: 'id' },
    caption: { type: String, default: '' },
});
</script>

<template>
    <div class="overflow-hidden rounded border border-line bg-surface">
        <table class="w-full border-collapse text-left text-sm">
            <caption v-if="caption" class="sr-only">{{ caption }}</caption>
            <thead class="hidden border-b border-line bg-sunken/60 md:table-header-group">
                <tr>
                    <th v-for="column in columns" :key="column.key" scope="col" :class="['px-4 py-3 text-xs font-semibold uppercase tracking-[0.08em] text-ink-muted', column.class]">
                        {{ column.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <tr v-for="row in rows" :key="row[rowKey]" class="block px-4 py-3 transition-colors hover:bg-sunken/40 md:table-row md:p-0">
                    <td
                        v-for="(column, index) in columns"
                        :key="column.key"
                        :class="['flex items-start justify-between gap-4 py-1 align-middle md:table-cell md:px-4 md:py-3', index === 0 ? 'font-medium text-ink' : 'text-ink-muted', column.class]"
                    >
                        <span v-if="index !== 0" class="text-xs font-medium uppercase tracking-[0.08em] text-ink-subtle md:hidden">{{ column.label }}</span>
                        <span class="min-w-0 text-right md:text-left"><slot :name="`cell-${column.key}`" :row="row">{{ row[column.key] }}</slot></span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
