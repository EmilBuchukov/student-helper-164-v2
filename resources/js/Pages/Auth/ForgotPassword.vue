<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import FormField from '@/Components/ui/FormField.vue';
import Input from '@/Components/ui/Input.vue';

defineProps({ status: { type: String } });

const form = useForm({ email: '' });

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout :title="$t('auth.forgot_title')" :intro="$t('auth.forgot_intro')">
        <Head :title="$t('auth.forgot_title')" />

        <p v-if="status" class="mb-6 rounded border border-success/30 bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ status }}</p>

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <FormField v-slot="field" id="email" :label="$t('auth.email')" :error="form.errors.email">
                <Input :id="field.id" v-model="form.email" type="email" required autofocus autocomplete="username" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <Button type="submit" size="lg" block :loading="form.processing">{{ $t('auth.send_reset_link') }}</Button>
        </form>
    </GuestLayout>
</template>
