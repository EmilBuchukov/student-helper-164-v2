<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDateTime } from '@/format';
import { t } from '@/i18n';
import { confirmDialog } from '@/Components/ui/confirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    event: { type: Object, required: true },
    statuses: { type: Array, required: true },
});

const busy = ref(false);
const options = { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) };

const rsvp = (status) => router.post(route('events.rsvp', props.event.id), { status }, options);
const cancel = () => router.delete(route('events.rsvp.cancel', props.event.id), options);
const destroy = async () => {
    if (await confirmDialog(t('common.confirm_delete'))) router.delete(route('events.destroy', props.event.id));
};
</script>

<template>
    <Head :title="event.title" />

    <AppLayout>
        <template #header>
            <Link :href="event.club ? route('clubs.show', event.club.slug) : route('events.index')" class="text-sm text-ink-subtle hover:text-ink">
                ← {{ event.club ? event.club.name : $t('events.title') }}
            </Link>
        </template>

        <div>
            <div class="mx-auto max-w-3xl">
                <article class="space-y-5 rounded border border-line bg-surface p-4 sm:p-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-primary-ink">{{ event.club ? event.club.name : $t('events.school_wide') }}</p>
                        <h1 class="mt-1 break-words text-2xl font-semibold text-ink">{{ event.title }}</h1>
                    </div>

                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-ink-subtle">{{ $t('events.starts_at') }}</dt>
                            <dd class="font-medium text-ink">
                                {{ formatDateTime(event.starts_at) }}<template v-if="event.ends_at"> – {{ formatDateTime(event.ends_at) }}</template>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-ink-subtle">{{ $t('events.location') }}</dt>
                            <dd class="break-words font-medium text-ink">{{ event.location }}</dd>
                        </div>
                    </dl>

                    <div v-if="event.description" class="whitespace-pre-line break-words text-ink-muted">{{ event.description }}</div>

                    <p class="flex flex-wrap gap-x-4 text-sm text-ink-muted">
                        <span>{{ $t('events.going_count', { count: event.going_count }) }}</span>
                        <span>{{ $t('events.interested_count', { count: event.interested_count }) }}</span>
                        <span v-if="event.creator">{{ $t('events.created_by', { name: event.creator }) }}</span>
                    </p>

                    <div v-if="event.can.rsvp" class="flex flex-wrap items-center gap-2 border-t pt-4">
                        <span class="text-sm text-ink-muted">{{ $t('events.your_rsvp') }}:</span>
                        <button
                            v-for="status in statuses"
                            :key="status"
                            type="button"
                            :disabled="busy"
                            :aria-pressed="event.my_rsvp === status"
                            class="rounded-full px-4 py-1.5 text-sm font-medium disabled:opacity-50"
                            :class="event.my_rsvp === status ? 'bg-primary text-on-primary' : 'bg-sunken text-ink-muted hover:bg-sunken'"
                            @click="rsvp(status)"
                        >
                            {{ $t(`events.rsvp.${status}`) }}
                        </button>
                        <button v-if="event.my_rsvp" type="button" :disabled="busy" class="text-sm text-ink-subtle hover:text-ink" @click="cancel">
                            {{ $t('events.rsvp_cancel') }}
                        </button>
                    </div>

                    <div v-if="event.can.update || event.can.delete" class="flex gap-2 border-t pt-4">
                        <Link v-if="event.can.update" :href="route('events.edit', event.id)" class="rounded px-3 py-2 text-sm text-ink-muted hover:bg-sunken">{{ $t('common.edit') }}</Link>
                        <button v-if="event.can.delete" type="button" class="rounded px-3 py-2 text-sm text-primary-ink hover:bg-danger-soft" @click="destroy">{{ $t('common.delete') }}</button>
                    </div>
                </article>
            </div>
        </div>
    </AppLayout>
</template>
