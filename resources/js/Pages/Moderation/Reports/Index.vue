<script setup>
import PageHeader from '@/Components/ui/PageHeader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { formatDateTime } from '@/format';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    reports: { type: Object, required: true },
    filters: { type: Object, required: true },
    statuses: { type: Array, required: true },
    types: { type: Array, required: true },
    muteDurations: { type: Array, required: true },
    canSuspend: { type: Boolean, required: true },
});

const filter = reactive({ ...props.filters });
const apply = () => router.get(route('moderation.reports.index'), Object.fromEntries(Object.entries(filter).filter(([, v]) => v)), { preserveState: true });

const active = ref(null);
const form = useForm({ remove_content: true, mute_minutes: '', suspend: false, note: '' });

const openResolve = (report) => {
    active.value = report;
    form.reset();
    form.clearErrors();
    form.remove_content = !report.content?.deleted;
};

const resolve = () => {
    form.transform((data) => ({ ...data, mute_minutes: data.mute_minutes || null }))
        .post(route('moderation.reports.resolve', active.value.id), { preserveScroll: true, onSuccess: () => (active.value = null) });
};

const dismiss = (report) => router.post(route('moderation.reports.dismiss', report.id), {}, { preserveScroll: true });
</script>

<template>
    <Head :title="$t('moderation.title')" />

    <AppLayout>
        <PageHeader :title="$t('moderation.title')" :lead="$t('moderation.intro')" :kicker="$t('nav.admin')" />

        <div>
            <div class="mt-6 space-y-4">
                <div class="grid grid-cols-2 gap-3 border-b border-line pb-6 sm:flex">
                    <SelectInput v-model="filter.status" class="block w-full sm:w-52" :aria-label="$t('moderation.title')" @change="apply">
                        <option v-for="s in statuses" :key="s" :value="s">{{ $t(`reports.statuses.${s}`) }}</option>
                        <option value="all">{{ $t('moderation.all_statuses') }}</option>
                    </SelectInput>
                    <SelectInput v-model="filter.type" class="block w-full sm:w-52" :aria-label="$t('moderation.all_types')" @change="apply">
                        <option value="">{{ $t('moderation.all_types') }}</option>
                        <option v-for="type in types" :key="type" :value="type">{{ $t(`moderation.types.${type}`) }}</option>
                    </SelectInput>
                </div>

                <EmptyState v-if="reports.data.length === 0" :title="$t('moderation.empty')" />

                <article v-for="report in reports.data" :key="report.id" class="rounded border border-line bg-surface p-5 sm:p-6" :class="report.status === 'open' && 'shadow-[inset_4px_0_0_rgb(var(--primary))]'">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="kicker">{{ $t(`moderation.types.${report.type}`) }}</span>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="{ 'bg-primary-soft text-primary-ink': report.status === 'open', 'bg-success-soft text-success': report.status === 'resolved', 'bg-sunken text-ink-muted': report.status === 'dismissed' }"
                        >
                            {{ $t(`reports.statuses.${report.status}`) }}
                        </span>
                        <span class="text-ink-subtle">{{ $t('moderation.reported_by', { name: report.reporter ?? $t('common.deleted_user') }) }} · {{ formatDateTime(report.created_at) }}</span>
                    </div>
                    <p class="mt-3 break-words font-display text-xl font-semibold italic text-ink">„{{ report.reason }}“</p>

                    <div class="mt-4 border-s-2 border-line-strong bg-sunken/60 p-4 text-sm">
                        <template v-if="report.content">
                            <p v-if="report.content.title" class="break-words font-semibold text-ink">{{ report.content.title }}</p>
                            <p class="whitespace-pre-line break-words text-ink-muted">{{ report.content.text }}</p>
                            <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-subtle">
                                <span>{{ $t('moderation.author', { name: report.content.author ?? $t('common.deleted_user') }) }}</span>
                                <span v-if="report.content.context">{{ $t('moderation.in_channel', { name: report.content.context }) }}</span>
                                <span v-if="report.content.deleted" class="font-semibold text-primary-ink">{{ $t('moderation.content_deleted') }}</span>
                                <a v-if="report.content.url && !report.content.deleted" :href="report.content.url" class="link font-medium">{{ $t('moderation.open_content') }}</a>
                            </p>
                        </template>
                        <p v-else class="text-ink-subtle">{{ $t('moderation.content_missing') }}</p>
                    </div>

                    <p v-if="report.handler" class="mt-2 text-xs text-ink-subtle">
                        {{ $t('moderation.handled_by', { name: report.handler, date: formatDateTime(report.handled_at) }) }}<template v-if="report.note"> — {{ report.note }}</template>
                    </p>

                    <div v-if="report.status === 'open'" class="mt-5 flex flex-wrap gap-2">
                        <PrimaryButton type="button" @click="openResolve(report)">{{ $t('moderation.resolve') }}</PrimaryButton>
                        <SecondaryButton type="button" @click="dismiss(report)">{{ $t('moderation.dismiss') }}</SecondaryButton>
                    </div>
                </article>

                <Pagination :links="reports.links" />
            </div>
        </div>

        <Modal :show="active !== null" max-width="md" @close="active = null">
            <form v-if="active" class="space-y-4 p-6" @submit.prevent="resolve">
                <h3 class="font-display text-title-3 font-semibold text-ink">{{ $t('moderation.resolve_title') }}</h3>
                <label v-if="active.content && !active.content.deleted" class="flex items-center gap-2 text-sm text-ink-muted">
                    <Checkbox v-model:checked="form.remove_content" />
                    {{ $t('moderation.remove_content') }}
                </label>
                <label v-if="active.content?.author_id && !active.content.author_protected" class="block text-sm text-ink-muted">
                    {{ $t('moderation.mute_author') }}
                    <SelectInput v-model="form.mute_minutes" class="mt-1 block w-full">
                        <option value="">{{ $t('moderation.no_mute') }}</option>
                        <option v-for="d in muteDurations" :key="d" :value="d">{{ $t(`channels.durations.${d}`) }}</option>
                    </SelectInput>
                </label>
                <label v-if="canSuspend && active.content?.author_id && !active.content.author_protected" class="flex items-center gap-2 text-sm text-ink-muted">
                    <Checkbox v-model:checked="form.suspend" />
                    {{ $t('moderation.suspend_author') }}
                </label>
                <label class="block text-sm text-ink-muted">
                    {{ $t('moderation.note') }}
                    <textarea v-model="form.note" rows="2" maxlength="1000" class="mt-1 block w-full rounded border-line-strong text-sm focus:border-primary-ink focus:ring-focus/30" />
                </label>
                <InputError :message="form.errors.mute_minutes || form.errors.note" />
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="active = null">{{ $t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">{{ $t('moderation.resolve') }}</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
