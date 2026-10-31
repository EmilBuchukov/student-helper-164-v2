<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { formatDate, formatDateTime } from '@/format';
import { t } from '@/i18n';
import { confirmDialog } from '@/Components/ui/confirm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: { type: Object, required: true },
    counts: { type: Object, required: true },
    reports: { type: Array, required: true },
    mutes: { type: Array, required: true },
    roles: { type: Array, required: true },
    can: { type: Object, required: true },
});

const roleForm = useForm({ role: props.user.role });
const suspendForm = useForm({ reason: '' });

const saveRole = () => roleForm.patch(route('admin.users.role', props.user.id), { preserveScroll: true });
const suspend = async () => {
    if (await confirmDialog(t('admin.users.confirm_suspend'), { confirmLabel: t('confirm.confirm') })) {
        suspendForm.post(route('admin.users.suspend', props.user.id), { preserveScroll: true, onSuccess: () => suspendForm.reset() });
    }
};
const reactivate = () => router.post(route('admin.users.reactivate', props.user.id), {}, { preserveScroll: true });
</script>

<template>
    <Head :title="user.name" />

    <AppLayout>
        <template #header>
            <Link :href="route('admin.users.index')" class="text-sm text-ink-subtle hover:text-ink">← {{ $t('admin.users.title') }}</Link>
        </template>

        <div>
            <div class="mx-auto max-w-5xl space-y-6">
                <section class="rounded border border-line bg-surface p-4 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="text-2xl font-semibold text-ink">{{ user.name }}</h1>
                            <p class="break-all text-sm text-ink-muted">{{ user.email }}</p>
                            <p class="mt-1 text-sm text-ink-muted">
                                {{ $t(`roles.${user.role}`) }} · {{ user.class_name ?? $t('common.none') }} ·
                                {{ $t('admin.users.registered', { date: formatDate(user.created_at) }) }}
                            </p>
                            <p v-if="user.whitelisted" class="mt-1 text-xs text-ink-subtle">{{ $t('admin.users.whitelisted') }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-sm font-medium" :class="user.is_active ? 'bg-success-soft text-success' : 'bg-sunken text-ink-muted'">
                            {{ user.is_active ? $t('admin.users.active') : $t('admin.users.inactive') }}
                        </span>
                    </div>

                    <p v-if="!can.manage" class="mt-4 text-sm text-ink-subtle">{{ $t('admin.users.self_note') }}</p>
                    <div v-else class="mt-6 grid gap-6 border-t pt-4 sm:grid-cols-2">
                        <form class="space-y-2" @submit.prevent="saveRole">
                            <label for="role" class="block text-sm font-medium text-ink-muted">{{ $t('admin.users.change_role') }}</label>
                            <div class="flex gap-2">
                                <SelectInput id="role" v-model="roleForm.role" class="block w-full">
                                    <option v-for="r in roles" :key="r" :value="r">{{ $t(`roles.${r}`) }}</option>
                                </SelectInput>
                                <PrimaryButton :disabled="roleForm.processing || roleForm.role === user.role">{{ $t('common.save') }}</PrimaryButton>
                            </div>
                        </form>
                        <div class="space-y-2">
                            <template v-if="user.is_active">
                                <label for="reason" class="block text-sm font-medium text-ink-muted">{{ $t('admin.users.suspend_reason') }}</label>
                                <div class="flex gap-2">
                                    <input id="reason" v-model="suspendForm.reason" maxlength="500" class="block w-full rounded border-line-strong text-sm focus:border-primary-ink focus:ring-focus/30" />
                                    <button type="button" :disabled="suspendForm.processing" class="shrink-0 rounded bg-primary px-4 py-2 text-sm font-semibold text-on-primary hover:bg-primary-hover disabled:opacity-50" @click="suspend">
                                        {{ $t('admin.users.suspend') }}
                                    </button>
                                </div>
                            </template>
                            <button v-else type="button" class="rounded bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800" @click="reactivate">
                                {{ $t('admin.users.reactivate') }}
                            </button>
                        </div>
                    </div>
                </section>

                <section class="rounded border border-line bg-surface p-4 sm:p-6">
                    <h2 class="font-semibold text-ink">{{ $t('admin.users.content') }}</h2>
                    <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div v-for="(value, key) in counts" :key="key" class="rounded bg-sunken/60 p-3">
                            <dt class="text-xs text-ink-subtle">{{ $t(`admin.users.counts.${key}`) }}</dt>
                            <dd class="text-lg font-semibold text-ink">{{ value }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded border border-line bg-surface p-4 sm:p-6">
                    <h2 class="font-semibold text-ink">{{ $t('admin.users.report_history') }}</h2>
                    <p v-if="reports.length === 0" class="mt-2 text-sm text-ink-subtle">{{ $t('admin.users.no_reports') }}</p>
                    <ul v-else class="mt-3 divide-y divide-line text-sm">
                        <li v-for="r in reports" :key="r.id" class="flex flex-wrap gap-x-3 py-2">
                            <span class="font-medium">{{ $t(`moderation.types.${r.type}`) }}</span>
                            <span class="min-w-0 flex-1 break-words text-ink-muted">{{ r.reason }}</span>
                            <span class="text-xs text-ink-subtle">{{ $t(`reports.statuses.${r.status}`) }} · {{ formatDateTime(r.created_at) }}</span>
                        </li>
                    </ul>
                </section>

                <section class="rounded border border-line bg-surface p-4 sm:p-6">
                    <h2 class="font-semibold text-ink">{{ $t('admin.users.mute_history') }}</h2>
                    <p v-if="mutes.length === 0" class="mt-2 text-sm text-ink-subtle">{{ $t('admin.users.no_mutes') }}</p>
                    <ul v-else class="mt-3 divide-y divide-line text-sm">
                        <li v-for="m in mutes" :key="m.id" class="flex flex-wrap gap-x-3 py-2">
                            <span class="font-medium">{{ m.channel ?? $t('channels.mute_global') }}</span>
                            <span class="text-ink-muted">{{ $t('channels.until', { time: formatDateTime(m.until) }) }}</span>
                            <span v-if="m.active" class="text-xs font-semibold text-accent-ink">{{ $t('admin.users.active_mute') }}</span>
                            <span v-if="m.reason" class="min-w-0 flex-1 break-words text-ink-subtle">{{ m.reason }}</span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
