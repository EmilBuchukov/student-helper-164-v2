<script setup>
import { ArrowLeft } from 'lucide-vue-next';
import Azulejo from '@/Components/ui/Azulejo.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import EventCard from '@/Components/EventCard.vue';
import { t } from '@/i18n';
import { confirmDialog } from '@/Components/ui/confirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    club: { type: Object, required: true },
    events: { type: Array, required: true },
});

const busy = ref(false);
const options = { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) };

const join = () => router.post(route('clubs.join', props.club.slug), {}, options);
const leave = async () => {
    if (await confirmDialog(t('clubs.confirm_leave'), { danger: false })) router.delete(route('clubs.leave', props.club.slug), options);
};
const destroy = async () => {
    if (await confirmDialog(t('common.confirm_delete'))) router.delete(route('clubs.destroy', props.club.slug));
};
</script>

<template>
    <Head :title="club.name" />

    <AppLayout>
        <template #header>
            <Link :href="route('clubs.index')" class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-ink-muted hover:text-ink"><ArrowLeft class="size-4" aria-hidden="true" />{{ $t('clubs.title') }}</Link>
        </template>

        <div>
            <div class="mx-auto max-w-5xl space-y-10">
                <section class="relative overflow-hidden rounded bg-sand-900 p-6 text-sand-50 sm:p-10"><div class="absolute inset-y-0 right-0 hidden w-40 opacity-50 sm:block lg:w-56" aria-hidden="true"><Azulejo /></div>
                    <div class="relative flex flex-wrap items-end justify-between gap-6 sm:pe-40 lg:pe-56">
                        <div class="min-w-0">
                            <p class="kicker !text-accent">{{ $t('clubs.title') }}</p><h1 class="mt-3 break-words font-display text-display font-semibold">{{ club.name }}</h1>
                            <p class="mt-3 text-sand-200">
                                {{ $t('clubs.leader') }}: {{ club.leader ?? $t('clubs.no_leader') }} ·
                                {{ $t('clubs.members_count', { count: club.members_count }) }}
                            </p>
                            <span v-if="!club.is_active" class="mt-3 inline-block rounded-full bg-sand-50/15 px-2.5 py-0.5 text-xs text-sand-50">{{ $t('clubs.inactive') }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-if="club.is_member && club.can.leave"
                                type="button"
                                :disabled="busy"
                                class="inline-flex min-h-11 items-center rounded border border-sand-50/40 px-4 text-sm font-semibold text-sand-50 transition hover:bg-sand-50/10 active:scale-[0.98] disabled:opacity-50"
                                @click="leave"
                            >
                                {{ $t('clubs.leave') }}
                            </button>
                            <button
                                v-else-if="club.can.join"
                                type="button"
                                :disabled="busy"
                                class="inline-flex min-h-11 items-center rounded bg-accent px-5 text-sm font-semibold text-on-accent transition hover:brightness-95 active:scale-[0.98] disabled:opacity-50"
                                @click="join"
                            >
                                {{ $t('clubs.join') }}
                            </button>
                            <Link v-if="club.can.update" :href="route('clubs.edit', club.slug)" class="inline-flex min-h-11 items-center rounded px-3 text-sm text-sand-100 hover:bg-sand-50/10">{{ $t('common.edit') }}</Link>
                            <button v-if="club.can.delete" type="button" class="inline-flex min-h-11 items-center rounded px-3 text-sm text-sand-100 hover:bg-primary/40" @click="destroy">{{ $t('common.delete') }}</button>
                        </div>
                    </div>
                    <p v-if="$page.props.auth.user.role === 'teacher'" class="relative mt-4 text-sm text-sand-200">{{ $t('clubs.teachers_view_only') }}</p>
                    <div v-if="club.description" class="relative mt-6 max-w-prose sm:pe-40 lg:pe-56 whitespace-pre-line break-words text-lg leading-relaxed text-sand-100">{{ club.description }}</div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="font-display text-title-2 font-semibold text-ink">{{ $t('clubs.upcoming_events') }}</h2>
                        <Link
                            v-if="club.can.manage_events"
                            :href="route('events.create', { club: club.slug })"
                            class="inline-flex min-h-11 items-center rounded bg-ink px-4 text-sm font-semibold text-canvas hover:bg-ink/85"
                        >
                            {{ $t('events.new') }}
                        </Link>
                    </div>
                    <EmptyState v-if="events.length === 0" :title="$t('clubs.no_events')" />
                    <EventCard v-for="event in events" :key="event.id" :event="event" />
                </section>
            </div>
        </div>
    </AppLayout>
</template>
