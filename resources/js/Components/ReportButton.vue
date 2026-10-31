<script setup>
import { useForm } from '@inertiajs/vue3';
import { Flag } from 'lucide-vue-next';
import { ref } from 'vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import FormField from '@/Components/ui/FormField.vue';
import Textarea from '@/Components/ui/Textarea.vue';

const props = defineProps({
    type: { type: String, required: true },
    id: { type: Number, required: true },
});

const open = ref(false);
const form = useForm({ type: props.type, id: props.id, reason: '' });

const close = () => {
    open.value = false;
    form.reset('reason');
    form.clearErrors();
};

const submit = () => {
    form.post(route('reports.store'), { preserveScroll: true, preserveState: true, onSuccess: close });
};
</script>

<template>
    <button type="button" class="inline-flex min-h-9 items-center gap-1.5 rounded px-2 text-sm text-ink-subtle transition-colors hover:bg-sunken hover:text-danger" @click="open = true">
        <Flag class="size-3.5" aria-hidden="true" />
        {{ $t('reports.report') }}
    </button>
    <Dialog v-model:open="open" :title="$t('reports.title')" @update:open="(value) => !value && close()">
        <form :id="`report-form-${type}-${id}`" @submit.prevent="submit">
            <FormField v-slot="field" :id="`report-${type}-${id}`" :label="$t('reports.reason')" :error="form.errors.reason || form.errors.type || form.errors.id">
                <Textarea :id="field.id" v-model="form.reason" rows="3" maxlength="1000" required :invalid="field.invalid" :aria-describedby="field.describedby" :placeholder="$t('reports.reason_placeholder')" />
            </FormField>
        </form>
        <template #footer>
            <Button variant="secondary" @click="close">{{ $t('common.cancel') }}</Button>
            <Button type="submit" :form="`report-form-${type}-${id}`" :loading="form.processing">{{ $t('reports.submit') }}</Button>
        </template>
    </Dialog>
</template>
