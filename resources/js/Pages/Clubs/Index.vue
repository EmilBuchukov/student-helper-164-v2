<script setup>
import { CalendarDays, Plus, Users } from 'lucide-vue-next';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    clubs: { type: Array, required: true },
    can: { type: Object, required: true },
});
</script>

<template>
    <Head :title="$t('clubs.title')" />

    <AppLayout>
        <PageHeader :title="$t('clubs.title')" :lead="$t('clubs.intro')" :kicker="$t('app.name')">
            <template #actions>
                <Button :href="route('events.index')" variant="secondary"><CalendarDays aria-hidden="true" />{{ $t('events.title') }}</Button>
                <Button v-if="can.create" :href="route('clubs.create')"><Plus aria-hidden="true" />{{ $t('clubs.new') }}</Button>
            </template>
        </PageHeader>

        <EmptyState v-if="clubs.length === 0" class="mt-8" :title="$t('clubs.empty')" />

        <ol v-else class="mt-8 grid gap-x-10 border-t-2 border-ink md:grid-cols-2">
            <li v-for="(club, i) in clubs" :key="club.id" class="border-b border-line">
                <Link :href="route('clubs.show', club.slug)" class="group flex h-full gap-5 py-6" :class="{ 'opacity-60': !club.is_active }">
                    <span class="w-12 shrink-0 font-display text-4xl font-semibold leading-none tabular-nums text-primary-ink" aria-hidden="true">{{ String(i + 1).padStart(2, '0') }}</span>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h2 class="break-words font-display text-title-3 font-semibold text-ink group-hover:underline group-hover:decoration-accent group-hover:decoration-2 group-hover:underline-offset-4">{{ club.name }}</h2>
                            <Badge v-if="club.is_member" tone="success">{{ $t('clubs.member_badge') }}</Badge>
                            <Badge v-else-if="!club.is_active">{{ $t('clubs.inactive') }}</Badge>
                        </div>
                        <p class="mt-2 flex-1 break-words text-ink-muted">{{ club.excerpt }}</p>
                        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm text-ink-subtle">
                            <span>{{ club.leader ?? $t('clubs.no_leader') }}</span>
                            <span class="inline-flex items-center gap-1"><Users class="size-4" aria-hidden="true" />{{ $t('clubs.members_count', { count: club.members_count }) }}</span>
                            <span class="inline-flex items-center gap-1"><CalendarDays class="size-4" aria-hidden="true" />{{ $t('clubs.upcoming_count', { count: club.upcoming_events_count }) }}</span>
                        </div>
                    </div>
                </Link>
            </li>
        </ol>
    </AppLayout>
</template>
