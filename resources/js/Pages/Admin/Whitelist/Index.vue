<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDate } from '@/format';
import { t } from '@/i18n';
import { confirmDialog } from '@/Components/ui/confirm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    entries: { type: Object, required: true },
    filters: { type: Object, required: true },
    stats: { type: Object, required: true },
    classes: { type: Array, required: true },
});

const filter = reactive({ ...props.filters });
const apply = () => router.get(route('admin.whitelist.index'), Object.fromEntries(Object.entries(filter).filter(([, v]) => v)), { preserveState: true });

const form = useForm({ email: '', class_id: '' });
const add = () => form.post(route('admin.whitelist.store'), { preserveScroll: true, onSuccess: () => form.reset('email') });
const remove = async (entry) => {
    if (await confirmDialog(t('common.confirm_delete'))) router.delete(route('admin.whitelist.destroy', entry.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="$t('admin.whitelist.title')" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="font-display text-title-1 font-semibold text-ink">{{ $t('admin.whitelist.title') }}</h1>
                    <p class="mt-1 text-sm text-ink-muted">
                        {{ $t('admin.whitelist.intro') }}
                        {{ $t('admin.whitelist.stats', { total: stats.total, claimed: stats.claimed, pending: stats.total - stats.claimed }) }}
                    </p>
                </div>
                <Link :href="route('admin.students.import')" class="rounded border border-line-strong border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink-muted hover:bg-sunken">
                    {{ $t('admin.whitelist.import_csv') }}
                </Link>
            </div>
        </template>

        <div>
            <div class="mx-auto max-w-5xl space-y-4">
                <form class="rounded border border-line bg-surface p-4" @submit.prevent="add">
                    <h3 class="text-sm font-semibold text-ink">{{ $t('admin.whitelist.add_title') }}</h3>
                    <div class="mt-2 grid gap-3 sm:grid-cols-[1fr_12rem_auto]">
                        <div>
                            <TextInput v-model="form.email" type="email" required class="block w-full text-sm" :placeholder="$t('admin.whitelist.email')" />
                            <InputError class="mt-1" :message="form.errors.email" />
                        </div>
                        <div>
                            <SelectInput v-model="form.class_id" required class="block w-full">
                                <option value="" disabled>{{ $t('admin.whitelist.choose_class') }}</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </SelectInput>
                            <InputError class="mt-1" :message="form.errors.class_id" />
                        </div>
                        <PrimaryButton class="h-10 justify-center" :disabled="form.processing">{{ $t('admin.whitelist.add') }}</PrimaryButton>
                    </div>
                </form>

                <form class="flex flex-wrap gap-3 rounded border border-line bg-surface p-4" @submit.prevent="apply">
                    <TextInput v-model="filter.q" type="search" class="block min-w-[12rem] flex-1 text-sm" :placeholder="$t('admin.whitelist.search')" />
                    <SelectInput v-model="filter.status" class="block w-48" @change="apply">
                        <option value="">{{ $t('admin.whitelist.all') }}</option>
                        <option value="pending">{{ $t('admin.whitelist.pending') }}</option>
                        <option value="claimed">{{ $t('admin.whitelist.claimed') }}</option>
                    </SelectInput>
                </form>

                <EmptyState v-if="entries.data.length === 0" :title="$t('admin.whitelist.empty')" />
                <ul v-else class="divide-y divide-line rounded border border-line bg-surface">
                    <li v-for="entry in entries.data" :key="entry.id" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 text-sm">
                        <span class="min-w-0 flex-1 break-all font-medium text-ink">{{ entry.email }}</span>
                        <span class="text-ink-muted">{{ entry.class_name ?? $t('common.none') }}</span>
                        <span v-if="entry.claimed" class="rounded-full bg-success-soft px-2 py-0.5 text-xs text-success">
                            {{ $t('admin.whitelist.claimed') }}<template v-if="entry.claimed_at"> · {{ formatDate(entry.claimed_at) }}</template>
                        </span>
                        <span v-else class="rounded-full bg-accent-soft px-2 py-0.5 text-xs text-accent-ink">{{ $t('admin.whitelist.pending') }}</span>
                        <Link v-if="entry.claimed_by_id" :href="route('admin.users.show', entry.claimed_by_id)" class="text-xs text-primary-ink hover:underline">{{ entry.claimed_by }}</Link>
                        <button v-else type="button" class="text-xs text-primary-ink hover:underline" @click="remove(entry)">{{ $t('common.delete') }}</button>
                    </li>
                </ul>

                <Pagination :links="entries.links" />
            </div>
        </div>
    </AppLayout>
</template>
