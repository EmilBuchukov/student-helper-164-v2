<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import ProfileInformation from './Partials/ProfileInformation.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';

defineProps({ profile: { type: Object, required: true } });
</script>

<template>
    <Head :title="$t('profile.title')" />

    <AppLayout>
        <PageHeader :title="profile.name" :kicker="$t('profile.title')">
            <template #before><Avatar :name="profile.name" size="lg" class="mb-4" /></template>
        </PageHeader>

        <div class="mt-8 divide-y divide-line">
            <ProfileInformation :profile="profile" class="py-8" />
            <UpdatePasswordForm class="py-8" />
        </div>
    </AppLayout>
</template>
