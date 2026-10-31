<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import FormField from '@/Components/ui/FormField.vue';
import Input from '@/Components/ui/Input.vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout :title="$t('auth.login_title')">
        <Head :title="$t('auth.login_title')" />

        <p v-if="status" class="mb-6 rounded border border-success/30 bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ status }}</p>

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <FormField v-slot="field" id="email" :label="$t('auth.email')" :error="form.errors.email">
                <Input :id="field.id" v-model="form.email" type="email" required autofocus autocomplete="username" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>

            <FormField v-slot="field" id="password" :label="$t('auth.password')" :error="form.errors.password">
                <Input :id="field.id" v-model="form.password" type="password" required autocomplete="current-password" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <Checkbox v-model="form.remember" name="remember">{{ $t('auth.remember') }}</Checkbox>
                <Link v-if="canResetPassword" :href="route('password.request')" class="link text-sm">{{ $t('auth.forgot') }}</Link>
            </div>

            <Button type="submit" size="lg" block :loading="form.processing">{{ $t('auth.login') }}</Button>
        </form>

        <p class="mt-8 border-t border-line pt-6 text-ink-muted">
            {{ $t('auth.no_account') }}
            <Link :href="route('register')" class="link">{{ $t('auth.register') }}</Link>
        </p>
    </GuestLayout>
</template>
