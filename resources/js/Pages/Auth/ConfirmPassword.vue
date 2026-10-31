<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import FormField from '@/Components/ui/FormField.vue';
import Input from '@/Components/ui/Input.vue';

const form = useForm({ password: '' });

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout :title="$t('auth.confirm_title')" :intro="$t('auth.confirm_intro')">
        <Head :title="$t('auth.confirm_title')" />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <FormField v-slot="field" id="password" :label="$t('auth.password')" :error="form.errors.password">
                <Input :id="field.id" v-model="form.password" type="password" required autofocus autocomplete="current-password" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <Button type="submit" size="lg" block :loading="form.processing">{{ $t('auth.confirm') }}</Button>
        </form>
    </GuestLayout>
</template>
