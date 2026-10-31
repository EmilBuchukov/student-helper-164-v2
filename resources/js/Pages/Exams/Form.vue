<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    exam: { type: Object, default: null },
    scopeOptions: { type: Array, required: true },
    types: { type: Array, required: true },
    subjects: { type: Array, required: true },
});

const form = useForm({
    subject: props.exam?.subject ?? '',
    title: props.exam?.title ?? '',
    description: props.exam?.description ?? '',
    exam_date: props.exam?.exam_date ?? '',
    type: props.exam?.type ?? 'test',
    scope: props.exam?.scope ?? props.scopeOptions[0]?.value ?? '',
});

const submit = () => {
    if (props.exam) {
        form.put(route('exams.update', props.exam.id));
    } else {
        form.post(route('exams.store'));
    }
};
</script>

<template>
    <Head :title="exam ? $t('exams.edit') : $t('exams.new')" />

    <AppLayout>
        <template #header>
            <h1 class="font-display text-title-1 font-semibold text-ink">
                {{ exam ? $t('exams.edit') : $t('exams.new') }}
            </h1>
        </template>

        <div>
            <div class="mx-auto max-w-2xl">
                <form class="space-y-4 rounded border border-line bg-surface p-4 sm:p-6" @submit.prevent="submit">
                    <div>
                        <InputLabel for="subject" :value="$t('exams.subject')" />
                        <TextInput
                            id="subject"
                            v-model="form.subject"
                            list="subject-suggestions"
                            class="mt-1 block w-full"
                            maxlength="100"
                            required
                        />
                        <datalist id="subject-suggestions">
                            <option v-for="s in subjects" :key="s" :value="s" />
                        </datalist>
                        <InputError class="mt-2" :message="form.errors.subject" />
                    </div>
                    <div>
                        <InputLabel for="title" :value="$t('exams.title_field')" />
                        <TextInput id="title" v-model="form.title" class="mt-1 block w-full" maxlength="255" required />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="exam_date" :value="$t('exams.date')" />
                            <TextInput id="exam_date" v-model="form.exam_date" type="date" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.exam_date" />
                        </div>
                        <div>
                            <InputLabel for="type" :value="$t('exams.type')" />
                            <SelectInput id="type" v-model="form.type" class="mt-1 block w-full">
                                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </SelectInput>
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="scope" :value="$t('scope.label')" />
                        <SelectInput id="scope" v-model="form.scope" class="mt-1 block w-full">
                            <option value="" disabled>{{ $t('scope.choose') }}</option>
                            <option v-for="o in scopeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </SelectInput>
                        <p class="mt-1 text-xs text-ink-subtle">{{ $t('exams.scope_hint') }}</p>
                        <InputError class="mt-2" :message="form.errors.scope" />
                    </div>
                    <div>
                        <InputLabel for="description" :value="$t('exams.description')" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            maxlength="5000"
                            class="mt-1 block w-full rounded border-line-strong focus:border-primary-ink focus:ring-focus/30"
                        />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <Link
                            :href="exam ? route('exams.show', exam.id) : route('exams.index')"
                            class="text-sm text-ink-muted hover:text-ink"
                        >
                            {{ $t('common.cancel') }}
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            {{ form.processing ? $t('common.loading') : $t('common.save') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
