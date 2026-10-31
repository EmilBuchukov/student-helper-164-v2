<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowBigUp, CheckCircle2, MessageSquare, Plus, Search } from 'lucide-vue-next';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import FormField from '@/Components/ui/FormField.vue';
import Input from '@/Components/ui/Input.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Select from '@/Components/ui/Select.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDateTime } from '@/format';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    questions: { type: Object, required: true },
    filters: { type: Object, required: true },
    subjects: { type: Array, required: true },
    scopeOptions: { type: Array, required: true },
    can: { type: Object, required: true },
});

const sorts = ['newest', 'votes', 'unresolved'];
const form = reactive({ ...props.filters });
const loading = ref(false);

const apply = () => {
    const query = Object.fromEntries(Object.entries(form).filter(([k, v]) => v && !(k === 'sort' && v === 'newest')));
    router.get(route('questions.index'), query, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};

const setSort = (sort) => {
    form.sort = sort;
    apply();
};

const reset = () => {
    Object.assign(form, { q: '', subject: '', scope: '', sort: 'newest' });
    apply();
};
</script>

<template>
    <Head :title="$t('questions.title')" />

    <AppLayout>
        <PageHeader :title="$t('questions.title')" :lead="$t('questions.intro')" :kicker="$t('app.name')">
            <template v-if="can.create" #actions>
                <Button :href="route('questions.create')"><Plus aria-hidden="true" />{{ $t('questions.new') }}</Button>
            </template>
        </PageHeader>

        <div class="mt-6 grid gap-8 lg:grid-cols-12">
            <form class="flex flex-col gap-4 lg:col-span-4 lg:sticky lg:top-24 lg:self-start xl:col-span-3" role="search" @submit.prevent="apply">
                <FormField v-slot="field" :label="$t('questions.search')">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-subtle" aria-hidden="true" />
                        <Input :id="field.id" v-model="form.q" type="search" maxlength="100" class="ps-9" :placeholder="$t('questions.search_placeholder')" />
                    </div>
                </FormField>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-1">
                    <FormField v-slot="field" :label="$t('questions.subject')">
                        <Select :id="field.id" v-model="form.subject">
                            <option value="">{{ $t('questions.all_subjects') }}</option>
                            <option v-for="s in subjects" :key="s" :value="s">{{ s }}</option>
                        </Select>
                    </FormField>
                    <FormField v-slot="field" :label="$t('scope.label')">
                        <Select :id="field.id" v-model="form.scope">
                            <option value="">{{ $t('questions.all_scopes') }}</option>
                            <option value="school">{{ $t('scope.school_wide') }}</option>
                            <option v-for="o in scopeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </Select>
                    </FormField>
                </div>
                <div class="flex gap-2">
                    <Button type="submit" variant="secondary" class="flex-1" :loading="loading">{{ $t('common.filter') }}</Button>
                    <Button variant="ghost" @click="reset">{{ $t('common.reset') }}</Button>
                </div>
            </form>

            <div class="min-w-0 lg:col-span-8 xl:col-span-9">
                <div class="flex flex-wrap items-center gap-1 border-b border-line" role="group" :aria-label="$t('questions.sort')">
                    <button
                        v-for="s in sorts"
                        :key="s"
                        type="button"
                        :aria-pressed="form.sort === s"
                        :class="[
                            'relative inline-flex min-h-11 items-center px-3 text-sm font-medium transition-colors after:absolute after:inset-x-2 after:bottom-0 after:h-0.5 after:origin-left after:bg-primary after:transition-transform after:duration-200',
                            form.sort === s ? 'text-ink after:scale-x-100' : 'text-ink-muted after:scale-x-0 hover:text-ink',
                        ]"
                        @click="setSort(s)"
                    >
                        {{ $t(`questions.sorts.${s}`) }}
                    </button>
                </div>

                <EmptyState v-if="questions.data.length === 0" class="mt-6" :title="$t('questions.empty')" :hint="can.create ? $t('questions.empty_hint') : ''">
                    <Button v-if="can.create" :href="route('questions.create')"><Plus aria-hidden="true" />{{ $t('questions.new') }}</Button>
                </EmptyState>

                <TransitionGroup v-else tag="ul" name="list" class="relative divide-y divide-line transition-opacity" :class="{ 'opacity-60': loading }" :aria-busy="loading">
                    <li v-for="question in questions.data" :key="question.id">
                        <Link :href="route('questions.show', question.id)" class="group -mx-2 flex gap-4 rounded px-2 py-5 transition-colors hover:bg-surface sm:gap-6">
                            <div class="hidden w-14 shrink-0 flex-col items-center pt-1 sm:flex">
                                <span class="font-display text-3xl font-semibold tabular-nums leading-none text-ink">{{ question.votes_count }}</span>
                                <ArrowBigUp class="mt-1 size-4 text-ink-subtle" aria-hidden="true" /><span class="sr-only">{{ $t('questions.votes_count', { count: question.votes_count }) }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <span v-if="question.subject" class="text-xs font-semibold uppercase tracking-[0.1em] text-primary-ink">{{ question.subject }}</span>
                                    <span class="text-xs text-ink-subtle">{{ question.audience }}</span>
                                </div>
                                <p class="mt-1.5 break-words font-display text-lg font-semibold leading-snug text-ink group-hover:underline group-hover:decoration-accent group-hover:decoration-2 group-hover:underline-offset-4 sm:text-xl">{{ question.title }}</p>
                                <p class="mt-1.5 line-clamp-2 break-words text-ink-muted">{{ question.excerpt }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-subtle">
                                    <Badge :tone="question.resolved ? 'success' : 'accent'">
                                        <CheckCircle2 v-if="question.resolved" aria-hidden="true" />{{ question.resolved ? $t('questions.resolved') : $t('questions.unresolved') }}
                                    </Badge>
                                    <span class="inline-flex items-center gap-1"><MessageSquare class="size-4" aria-hidden="true" />{{ $t('questions.answers_count', { count: question.answers_count }) }}</span>
                                    <span class="sm:hidden">{{ $t('questions.votes_count', { count: question.votes_count }) }}</span>
                                    <span>{{ question.author ?? $t('common.deleted_user') }} · {{ formatDateTime(question.created_at) }}</span>
                                </div>
                            </div>
                        </Link>
                    </li>
                </TransitionGroup>

                <div class="mt-6"><Pagination :links="questions.links" /></div>
            </div>
        </div>
    </AppLayout>
</template>
