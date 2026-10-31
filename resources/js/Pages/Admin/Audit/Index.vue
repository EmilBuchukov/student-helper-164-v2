<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AuditList from '@/Pages/Admin/Audit/AuditList.vue';
import Pagination from '@/Components/Pagination.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
    actions: { type: Array, required: true },
});

const action = ref(props.filters.action);
const apply = () => router.get(route('admin.audit.index'), action.value ? { action: action.value } : {}, { preserveState: true });
</script>

<template>
    <Head :title="$t('admin.audit.title')" />

    <AppLayout>
        <template #header>
            <h1 class="font-display text-title-1 font-semibold text-ink">{{ $t('admin.audit.title') }}</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $t('admin.audit.intro') }}</p>
        </template>

        <div>
            <div class="mx-auto max-w-5xl space-y-4">
                <div class="rounded border border-line bg-surface p-4">
                    <label class="block max-w-xs text-sm text-ink-muted">
                        {{ $t('admin.audit.action') }}
                        <SelectInput v-model="action" class="mt-1 block w-full" @change="apply">
                            <option value="">{{ $t('admin.audit.all_actions') }}</option>
                            <option v-for="a in actions" :key="a" :value="a">{{ $t(`admin.audit.actions.${a}`) }}</option>
                        </SelectInput>
                    </label>
                </div>
                <div class="rounded border border-line bg-surface p-4">
                    <AuditList :logs="logs.data" />
                </div>
                <Pagination :links="logs.links" />
            </div>
        </div>
    </AppLayout>
</template>
