<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { FileText, Plus } from 'lucide-vue-next';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import DateBlock from '@/Components/ui/DateBlock.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import FormField from '@/Components/ui/FormField.vue';
import Input from '@/Components/ui/Input.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import Select from '@/Components/ui/Select.vue';
import Skeleton from '@/Components/ui/Skeleton.vue';
import { formatDate } from '@/format';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    exams: { type: Object, required: true },
    filters: { type: Object, required: true },
    subjects: { type: Array, required: true },
    scopeOptions: { type: Array, required: true },
    can: { type: Object, required: true },
});

const form = reactive({ ...props.filters });
const loading = ref(false);

const apply = () => {
    const query = Object.fromEntries(Object.entries(form).filter(([, v]) => v));
    router.get(route('exams.index'), query, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};

const reset = () => {
    Object.keys(form).forEach((key) => (form[key] = ''));
    apply();
};
</script>

<template>
    <Head :title="$t('exams.title')" />

    <AppLayout>
        <PageHeader :title="$t('exams.title')" :lead="$t('exams.intro')" :kicker="$t('app.name')">
            <template v-if="can.create" #actions>
                <Button :href="route('exams.create')"><Plus aria-hidden="true" />{{ $t('exams.new') }}</Button>
            </template>
        </PageHeader>

        <form class="mt-6 grid grid-cols-2 gap-3 border-b border-line pb-6 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto]" @submit.prevent="apply">
            <FormField v-slot="field" :label="$t('exams.subject')" class="col-span-2 sm:col-span-1">
                <Select :id="field.id" v-model="form.subject">
                    <option value="">{{ $t('exams.all_subjects') }}</option>
                    <option v-for="s in subjects" :key="s" :value="s">{{ s }}</option>
                </Select>
            </FormField>
            <FormField v-slot="field" :label="$t('scope.label')" class="col-span-2 sm:col-span-1 lg:order-last lg:col-span-1">
                <Select :id="field.id" v-model="form.scope">
                    <option value="">{{ $t('exams.all_scopes') }}</option>
                    <option v-for="o in scopeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                </Select>
            </FormField>
            <FormField v-slot="field" :label="$t('exams.from')">
                <Input :id="field.id" v-model="form.from" type="date" />
            </FormField>
            <FormField v-slot="field" :label="$t('exams.to')">
                <Input :id="field.id" v-model="form.to" type="date" />
            </FormField>
            <div class="col-span-2 flex items-end gap-2 lg:order-last lg:col-span-1">
                <Button type="submit" variant="secondary" class="flex-1" :loading="loading">{{ $t('common.filter') }}</Button>
                <Button variant="ghost" @click="reset">{{ $t('common.reset') }}</Button>
            </div>
        </form>

        <div class="mt-6" aria-live="polite" :aria-busy="loading">
            <ul v-if="loading && exams.data.length === 0" class="space-y-3">
                <li v-for="n in 3" :key="n"><Skeleton class="h-24" /></li>
            </ul>

            <EmptyState v-else-if="exams.data.length === 0" :title="$t('exams.empty')" :hint="can.create ? $t('exams.empty_hint') : ''">
                <Button v-if="can.create" :href="route('exams.create')"><Plus aria-hidden="true" />{{ $t('exams.new') }}</Button>
            </EmptyState>

            <TransitionGroup v-else tag="ul" name="list" class="relative divide-y divide-line border-y border-line transition-opacity" :class="{ 'opacity-60': loading }">
                <li v-for="exam in exams.data" :key="exam.id">
                    <Link :href="route('exams.show', exam.id)" class="group -mx-2 flex items-center gap-4 rounded px-2 py-4 transition-colors hover:bg-surface sm:gap-6">
                        <DateBlock :date="exam.exam_date" :class="!exam.is_upcoming && 'opacity-60'" />
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-primary-ink">{{ exam.subject }}</p>
                            <p class="mt-1 break-words font-display text-lg font-semibold leading-snug text-ink group-hover:underline group-hover:decoration-accent group-hover:decoration-2 group-hover:underline-offset-4 sm:text-xl">{{ exam.title }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $t(`exams.types.${exam.type}`) }} · {{ exam.audience }}</p>
                        </div>
                        <div class="hidden shrink-0 flex-col items-end gap-2 sm:flex">
                            <Badge :tone="exam.is_upcoming ? 'success' : 'neutral'">{{ exam.is_upcoming ? $t('exams.upcoming') : $t('exams.past') }}</Badge>
                            <span class="inline-flex items-center gap-1 text-sm text-ink-subtle"><FileText class="size-4" aria-hidden="true" />{{ $t('exams.materials_count', { count: exam.materials_count }) }}</span>
                        </div>
                    </Link>
                </li>
            </TransitionGroup>
        </div>

        <div class="mt-6"><Pagination :links="exams.links" /></div>
    </AppLayout>
</template>
