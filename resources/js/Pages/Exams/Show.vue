<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download, FileText, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import DateBlock from '@/Components/ui/DateBlock.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import FormField from '@/Components/ui/FormField.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import Input from '@/Components/ui/Input.vue';
import Kicker from '@/Components/ui/Kicker.vue';
import { confirmDialog } from '@/Components/ui/confirm';
import { formatDate, formatDateTime, formatSize } from '@/format';
import { t } from '@/i18n';

const props = defineProps({
    exam: { type: Object, required: true },
    materials: { type: Array, required: true },
    upload: { type: Object, required: true },
});

const fileInput = ref(null);
const form = useForm({ title: '', file: null });

const submit = () => {
    form.post(route('exams.materials.store', props.exam.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const destroyExam = async () => {
    if (await confirmDialog(t('common.confirm_delete'))) {
        router.delete(route('exams.destroy', props.exam.id));
    }
};

const destroyMaterial = async (material) => {
    if (await confirmDialog(t('common.confirm_delete'))) {
        router.delete(route('materials.destroy', material.id), { preserveScroll: true });
    }
};

const accept = props.upload.extensions.map((e) => `.${e}`).join(',');
</script>

<template>
    <Head :title="exam.title" />

    <AppLayout>
        <Link :href="route('exams.index')" class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-ink-muted hover:text-ink">
            <ArrowLeft class="size-4" aria-hidden="true" />{{ $t('exams.title') }}
        </Link>

        <header class="mt-4 flex flex-col gap-6 border-b border-line pb-8 sm:flex-row sm:items-start">
            <DateBlock :date="exam.exam_date" size="lg" />
            <div class="min-w-0 flex-1">
                <Kicker>{{ exam.subject }}</Kicker>
                <h1 class="mt-2 break-words font-display text-title-1 font-semibold text-ink">{{ exam.title }}</h1>
                <p class="mt-3 text-ink-muted">{{ formatDate(exam.exam_date) }} · {{ $t(`exams.types.${exam.type}`) }} · {{ exam.audience }}</p>
                <p v-if="exam.creator" class="mt-1 text-sm text-ink-subtle">{{ $t('exams.created_by', { name: exam.creator }) }}</p>
            </div>
            <div v-if="exam.can.update || exam.can.delete" class="flex gap-2">
                <Button v-if="exam.can.update" :href="route('exams.edit', exam.id)" variant="secondary" size="sm"><Pencil aria-hidden="true" />{{ $t('common.edit') }}</Button>
                <Button v-if="exam.can.delete" variant="danger" size="sm" @click="destroyExam"><Trash2 aria-hidden="true" />{{ $t('common.delete') }}</Button>
            </div>
        </header>

        <div class="mt-8 grid gap-10 lg:grid-cols-12">
            <div class="space-y-10 lg:col-span-7">
                <p v-if="exam.description" class="prose-body">{{ exam.description }}</p>

                <section>
                    <h2 class="font-display text-title-3 font-semibold text-ink">{{ $t('materials.title') }}</h2>

                    <EmptyState v-if="materials.length === 0" class="mt-4" compact :title="$t('materials.empty')" />

                    <TransitionGroup v-else tag="ul" name="list" class="relative mt-4 divide-y divide-line border-y border-line">
                        <li v-for="material in materials" :key="material.id" class="flex items-center gap-4 py-4">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded bg-primary-soft text-primary-ink"><FileText class="size-5" aria-hidden="true" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="break-words font-medium text-ink">{{ material.title }}</p>
                                <p class="break-all text-sm text-ink-subtle">
                                    {{ material.original_name }} · {{ formatSize(material.size) }} · {{ material.author ?? $t('common.deleted_user') }} · {{ formatDateTime(material.created_at) }}
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <Button :href="material.download_url" external variant="secondary" size="sm" class="max-sm:size-11 max-sm:px-0" :aria-label="$t('materials.download')">
                                    <Download aria-hidden="true" /><span class="max-sm:sr-only">{{ $t('materials.download') }}</span>
                                </Button>
                                <IconButton v-if="material.can_delete" :label="$t('common.delete')" class="hover:text-danger" @click="destroyMaterial(material)"><Trash2 class="size-4" aria-hidden="true" /></IconButton>
                            </div>
                        </li>
                    </TransitionGroup>
                </section>
            </div>

            <aside class="lg:col-span-5">
                <section v-if="exam.can.upload" class="rounded border border-line bg-surface p-5 sm:p-6 lg:sticky lg:top-24">
                    <h2 class="font-display text-title-3 font-semibold text-ink">{{ $t('materials.upload_title') }}</h2>
                    <form class="mt-5 flex flex-col gap-5" @submit.prevent="submit">
                        <FormField v-slot="field" id="material-title" :label="$t('materials.title_field')" :error="form.errors.title">
                            <Input :id="field.id" v-model="form.title" maxlength="255" required :invalid="field.invalid" :aria-describedby="field.describedby" />
                        </FormField>
                        <FormField v-slot="field" id="material-file" :label="$t('materials.file')" :error="form.errors.file" :hint="$t('materials.allowed', { types: upload.extensions.join(', '), max: upload.max_mb })">
                            <input
                                :id="field.id"
                                ref="fileInput"
                                type="file"
                                :accept="accept"
                                :aria-describedby="field.describedby"
                                class="block w-full cursor-pointer rounded border border-dashed border-line-strong bg-canvas p-3 text-sm text-ink-muted file:me-3 file:cursor-pointer file:rounded file:border-0 file:bg-ink file:px-3 file:py-2 file:text-sm file:font-medium file:text-canvas hover:border-ink-subtle"
                                @input="form.file = $event.target.files[0]"
                            />
                        </FormField>
                        <div v-if="form.progress" class="h-1.5 overflow-hidden rounded-full bg-sunken" role="progressbar" :aria-valuenow="form.progress.percentage" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full bg-primary transition-[width] duration-200" :style="{ width: `${form.progress.percentage}%` }" />
                        </div>
                        <Button type="submit" :disabled="!form.file" :loading="form.processing">
                            {{ form.processing ? $t('materials.uploading') : $t('materials.upload') }}
                        </Button>
                    </form>
                </section>
                <p v-else-if="$page.props.auth.user.role === 'teacher'" class="rounded border border-line bg-surface p-5 text-ink-muted">{{ $t('materials.teacher_note') }}</p>
            </aside>
        </div>
    </AppLayout>
</template>
