<script setup>
defineProps({ profile: { type: Object, required: true } });
</script>

<template>
    <section class="grid gap-6 lg:grid-cols-3">
        <header>
            <h2 class="font-display text-title-3 font-semibold text-ink">{{ $t('profile.info_title') }}</h2>
            <p class="mt-2 text-ink-muted">{{ $t('profile.info_intro') }}</p>
        </header>
        <dl class="grid gap-px overflow-hidden rounded border border-line bg-line sm:grid-cols-2 lg:col-span-2">
            <div class="bg-surface p-4">
                <dt class="text-sm text-ink-subtle">{{ $t('profile.name') }}</dt>
                <dd class="mt-1 font-medium text-ink">{{ profile.name }}</dd>
            </div>
            <div class="bg-surface p-4">
                <dt class="text-sm text-ink-subtle">{{ $t('profile.email') }}</dt>
                <dd class="mt-1 break-all font-medium text-ink">{{ profile.email }}</dd>
            </div>
            <div class="bg-surface p-4">
                <dt class="text-sm text-ink-subtle">{{ $t('profile.class') }}</dt>
                <dd class="mt-1 font-medium text-ink">{{ profile.class_name ?? $t('dashboard.no_class') }}</dd>
            </div>
            <div class="bg-surface p-4">
                <dt class="text-sm text-ink-subtle">{{ $t('profile.role') }}</dt>
                <dd class="mt-1 font-medium text-ink">{{ profile.role ? $t(`roles.${profile.role}`) : $t('common.none') }}</dd>
            </div>
        </dl>
    </section>
</template>
