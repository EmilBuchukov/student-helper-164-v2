<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, BookOpenText, CalendarDays, MessageCircleQuestion, MessagesSquare, UsersRound } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Kicker from '@/Components/ui/Kicker.vue';
import { mainNav } from '@/navigation';

const user = computed(() => usePage().props.auth.user);
const today = new Date().toLocaleDateString('bg-BG', { weekday: 'long', day: 'numeric', month: 'long' });

const sections = {
    'exams.index': { icon: BookOpenText, intro: 'exams.intro' },
    'questions.index': { icon: MessageCircleQuestion, intro: 'questions.intro' },
    'channels.index': { icon: MessagesSquare, intro: 'channels.intro' },
    'clubs.index': { icon: UsersRound, intro: 'clubs.intro' },
    'events.index': { icon: CalendarDays, intro: 'events.intro' },
};
const shortcuts = computed(() =>
    mainNav.filter((item) => sections[item.route] && !item.hideFor?.includes(user.value.role)).map((item) => ({ ...item, ...sections[item.route] })),
);
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <AppLayout>
        <div class="grid gap-10 lg:grid-cols-12">
            <header class="lg:col-span-8">
                <Kicker><span class="first-letter:uppercase">{{ today }}</span></Kicker>
                <h1 class="mt-3 font-display text-title-1 font-semibold text-ink">{{ $t('dashboard.greeting', { name: user.name }) }}</h1>
                <p class="mt-3 max-w-[56ch] text-lg text-ink-muted">{{ $t('dashboard.intro') }}</p>
            </header>

            <aside class="rounded border border-line bg-surface p-5 lg:col-span-4" :aria-label="$t('dashboard.your_info')">
                <div class="flex items-center gap-3">
                    <Avatar :name="user.name" size="lg" />
                    <h2 class="text-sm font-semibold uppercase tracking-[0.1em] text-ink-muted">{{ $t('dashboard.your_info') }}</h2>
                </div>
                <dl class="mt-5 divide-y divide-line text-[0.9375rem]">
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-ink-subtle">{{ $t('dashboard.name') }}</dt>
                        <dd class="text-right font-medium text-ink" data-test="user-name">{{ user.name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-ink-subtle">{{ $t('dashboard.class') }}</dt>
                        <dd class="text-right font-medium text-ink" data-test="user-class">{{ user.class_name ?? $t('dashboard.no_class') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-ink-subtle">{{ $t('dashboard.role') }}</dt>
                        <dd class="text-right font-medium text-ink" data-test="user-role">{{ user.role ? $t(`roles.${user.role}`) : $t('common.none') }}</dd>
                    </div>
                </dl>
            </aside>
        </div>

        <section class="mt-12">
            <h2 class="kicker">{{ $t('dashboard.shortcuts') }}</h2>
            <ul class="mt-5 grid border-t border-line sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="(item, index) in shortcuts" :key="item.route" :class="['border-b border-line sm:odd:border-r lg:odd:border-r-0 lg:[&:not(:nth-child(3n))]:border-r', index === 0 && 'lg:col-span-1']">
                    <Link :href="route(item.route)" class="group flex h-full flex-col gap-3 p-5 transition-colors hover:bg-surface sm:p-6">
                        <span class="flex items-center justify-between">
                            <component :is="item.icon" class="size-6 text-primary-ink" aria-hidden="true" />
                            <ArrowUpRight class="size-5 text-ink-subtle transition-transform duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-ink" aria-hidden="true" />
                        </span>
                        <span class="font-display text-title-3 font-semibold text-ink">{{ $t(item.label) }}</span>
                        <span class="text-ink-muted">{{ $t(item.intro) }}</span>
                    </Link>
                </li>
            </ul>
        </section>
    </AppLayout>
</template>
