<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({ q: '', role: '', status: '' }) },
    roles: { type: Array, default: () => [] },
});

const form = reactive({ ...props.filters });
const loading = ref(false);

const apply = () => {
    const query = Object.fromEntries(Object.entries(form).filter(([, v]) => v));
    router.get(route('admin.users.index'), query, {
        preserveState: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};
</script>

<template>
    <Head :title="$t('admin.users.title')" />

    <AppLayout>
        <template #header>
            <h1 class="font-display text-title-1 font-semibold text-ink">{{ $t('admin.users.title') }}</h1>
            <p class="mt-1 text-sm text-ink-muted">
                {{ $t('admin.users.intro') }}
                {{ $t('admin.users.total', { count: users.total }) }}
            </p>
        </template>

        <div>
            <div class="space-y-4">
                <form class="grid gap-3 rounded border border-line bg-surface p-4 sm:grid-cols-4" @submit.prevent="apply">
                    <TextInput v-model="form.q" type="search" maxlength="100" class="block w-full text-sm sm:col-span-2" :placeholder="$t('admin.users.search')" />
                    <SelectInput v-model="form.role" class="block w-full" @change="apply">
                        <option value="">{{ $t('admin.users.all_roles') }}</option>
                        <option v-for="r in roles" :key="r" :value="r">{{ $t(`roles.${r}`) }}</option>
                    </SelectInput>
                    <SelectInput v-model="form.status" class="block w-full" @change="apply">
                        <option value="">{{ $t('admin.users.all_statuses') }}</option>
                        <option value="active">{{ $t('admin.users.active') }}</option>
                        <option value="suspended">{{ $t('admin.users.inactive') }}</option>
                    </SelectInput>
                </form>

                <EmptyState v-if="users.data.length === 0" :title="$t('admin.users.empty')" />

                <!-- Mobile: cards -->
                <ul class="space-y-3 md:hidden" :class="{ 'opacity-60': loading }">
                    <li v-for="user in users.data" :key="user.id">
                        <Link :href="route('admin.users.show', user.id)" class="block rounded border border-line bg-surface p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-medium text-ink">{{ user.name }}</p>
                                    <p class="break-all text-sm text-ink-subtle">{{ user.email }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="user.is_active ? 'bg-success-soft text-success' : 'bg-sunken text-ink-muted'">
                                    {{ user.is_active ? $t('admin.users.active') : $t('admin.users.inactive') }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-ink-muted">{{ $t(`roles.${user.role}`) }} · {{ user.class_name ?? $t('common.none') }}</p>
                        </Link>
                    </li>
                </ul>

                <!-- Desktop: table -->
                <div v-if="users.data.length" class="hidden overflow-hidden rounded border border-line bg-surface md:block" :class="{ 'opacity-60': loading }">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-sunken/60 text-left text-xs font-semibold uppercase text-ink-subtle">
                            <tr>
                                <th class="px-4 py-3">{{ $t('admin.users.name') }}</th>
                                <th class="px-4 py-3">{{ $t('admin.users.email') }}</th>
                                <th class="px-4 py-3">{{ $t('admin.users.role') }}</th>
                                <th class="px-4 py-3">{{ $t('admin.users.class') }}</th>
                                <th class="px-4 py-3">{{ $t('admin.users.status') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-4 py-3 font-medium text-ink">{{ user.name }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ user.email }}</td>
                                <td class="px-4 py-3">{{ $t(`roles.${user.role}`) }}</td>
                                <td class="px-4 py-3">{{ user.class_name ?? $t('common.none') }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="user.is_active ? 'bg-success-soft text-success' : 'bg-sunken text-ink-muted'">
                                        {{ user.is_active ? $t('admin.users.active') : $t('admin.users.inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('admin.users.show', user.id)" class="text-primary-ink hover:underline">{{ $t('admin.users.details') }}</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :links="users.links" />
            </div>
        </div>
    </AppLayout>
</template>
