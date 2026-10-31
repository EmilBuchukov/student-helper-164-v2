<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import FormField from '@/Components/ui/FormField.vue';
import Input from '@/Components/ui/Input.vue';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout :title="$t('auth.reset_title')">
        <Head :title="$t('auth.reset_title')" />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <FormField v-slot="field" id="email" :label="$t('auth.email')" :error="form.errors.email">
                <Input :id="field.id" v-model="form.email" type="email" required autofocus autocomplete="username" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <FormField v-slot="field" id="password" :label="$t('auth.password')" :error="form.errors.password">
                <Input :id="field.id" v-model="form.password" type="password" required autocomplete="new-password" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <FormField v-slot="field" id="password_confirmation" :label="$t('auth.password_confirmation')" :error="form.errors.password_confirmation">
                <Input :id="field.id" v-model="form.password_confirmation" type="password" required autocomplete="new-password" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <Button type="submit" size="lg" block :loading="form.processing">{{ $t('auth.reset_password') }}</Button>
        </form>
    </GuestLayout>
</template>
