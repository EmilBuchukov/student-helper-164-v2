<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import FormField from '@/Components/ui/FormField.vue';
import Input from '@/Components/ui/Input.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout :title="$t('auth.register_title')" :intro="$t('auth.register_intro')">
        <Head :title="$t('auth.register_title')" />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <FormField v-slot="field" id="name" :label="$t('auth.name')" :error="form.errors.name">
                <Input :id="field.id" v-model="form.name" type="text" required autofocus autocomplete="name" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <FormField v-slot="field" id="email" :label="$t('auth.email')" :error="form.errors.email">
                <Input :id="field.id" v-model="form.email" type="email" required autocomplete="username" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <FormField v-slot="field" id="password" :label="$t('auth.password')" :error="form.errors.password">
                <Input :id="field.id" v-model="form.password" type="password" required autocomplete="new-password" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <FormField v-slot="field" id="password_confirmation" :label="$t('auth.password_confirmation')" :error="form.errors.password_confirmation">
                <Input :id="field.id" v-model="form.password_confirmation" type="password" required autocomplete="new-password" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>

            <Button type="submit" size="lg" block :loading="form.processing">{{ $t('auth.register') }}</Button>
        </form>

        <p class="mt-8 border-t border-line pt-6 text-ink-muted">
            {{ $t('auth.already_registered') }}
            <Link :href="route('login')" class="link">{{ $t('auth.login') }}</Link>
        </p>
    </GuestLayout>
</template>
