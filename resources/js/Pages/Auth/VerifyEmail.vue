<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';

const props = defineProps({ status: { type: String } });

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout :title="$t('auth.verify_title')" :intro="$t('auth.verify_intro')">
        <Head :title="$t('auth.verify_title')" />

        <p v-if="verificationLinkSent" class="mb-6 rounded border border-success/30 bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ $t('auth.verify_sent') }}</p>

        <form class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" @submit.prevent="submit">
            <Button type="submit" size="lg" :loading="form.processing">{{ $t('auth.verify_resend') }}</Button>
            <Button :href="route('logout')" method="post" variant="ghost">{{ $t('auth.logout') }}</Button>
        </form>
    </GuestLayout>
</template>
