<script setup>
import { ArrowUpRight } from 'lucide-vue-next';
import PageHeader from '@/Components/ui/PageHeader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AuditList from '@/Pages/Admin/Audit/AuditList.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    counts: { type: Object, required: true },
    recent: { type: Array, required: true },
});

const cards = [
    { key: 'open_reports', route: 'moderation.reports.index', highlight: true },
    { key: 'active_mutes', route: 'moderation.reports.index' },
    { key: 'users', route: 'admin.users.index' },
    { key: 'students', route: 'admin.users.index', query: { role: 'student' } },
    { key: 'suspended', route: 'admin.users.index', query: { status: 'suspended' } },
    { key: 'pending_whitelist', route: 'admin.whitelist.index', query: { status: 'pending' } },
    { key: 'exams', route: 'exams.index' },
    { key: 'questions', route: 'questions.index' },
];

const links = [
    { label: 'nav.moderation_reports', route: 'moderation.reports.index' },
    { label: 'nav.admin_users', route: 'admin.users.index' },
    { label: 'nav.admin_whitelist', route: 'admin.whitelist.index' },
    { label: 'nav.admin_import', route: 'admin.students.import' },
    { label: 'nav.admin_deleted', route: 'admin.deleted.index' },
    { label: 'nav.admin_audit', route: 'admin.audit.index' },
];
</script>

<template>
    <Head :title="$t('admin.dashboard.title')" />

    <AppLayout>
        <PageHeader :title="$t('admin.dashboard.title')" :lead="$t('admin.dashboard.intro')" :kicker="$t('nav.admin')" />

        <div class="grid grid-cols-2 md:grid-cols-4">
            <Link
                v-for="card in cards"
                :key="card.key"
                :href="route(card.route, card.query ?? {})"
                class="group relative flex flex-col-reverse border-b border-line p-4 transition-colors hover:bg-surface max-md:[&:nth-child(odd)]:border-e md:[&:not(:nth-child(4n))]:border-e sm:p-6"
            >
                <p class="mt-2 text-sm font-medium text-ink-muted">{{ $t(`admin.dashboard.counts.${card.key}`) }}</p>
                <p class="font-display text-5xl font-semibold leading-none tabular-nums sm:text-6xl" :class="card.highlight && counts[card.key] > 0 ? 'text-primary-ink' : 'text-ink'">
                    {{ counts[card.key] }}
                </p>
                <ArrowUpRight class="absolute right-4 top-4 size-4 text-ink-subtle opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true" />
            </Link>
        </div>

        <div class="mt-10 grid gap-10 lg:grid-cols-12">
            <section class="lg:col-span-4">
                <h2 class="kicker">{{ $t('admin.dashboard.quick_links') }}</h2>
                <ul class="mt-4 divide-y divide-line border-y border-line">
                    <li v-for="link in links" :key="link.route">
                        <Link :href="route(link.route)" class="group flex min-h-12 items-center justify-between py-2 font-medium text-ink hover:text-primary-ink">
                            {{ $t(link.label) }}<ArrowUpRight class="size-4 text-ink-subtle transition-transform duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true" />
                        </Link>
                    </li>
                </ul>
            </section>
            <section class="lg:col-span-8">
                <h2 class="kicker">{{ $t('admin.dashboard.recent') }}</h2>
                <AuditList class="mt-4" :logs="recent" />
            </section>
        </div>
    </AppLayout>
</template>
