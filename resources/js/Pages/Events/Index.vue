<script setup>
import { Plus } from 'lucide-vue-next';
import Button from '@/Components/ui/Button.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Skeleton from '@/Components/ui/Skeleton.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import EventCard from '@/Components/EventCard.vue';
import Pagination from '@/Components/Pagination.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    events: { type: Object, required: true },
    filters: { type: Object, required: true },
    clubs: { type: Array, required: true },
    can: { type: Object, required: true },
});

const form = reactive({ ...props.filters });
const loading = ref(false);

const apply = () => {
    const query = {};
    if (form.club) query.club = form.club;
    if (form.past) query.past = 1;
    router.get(route('events.index'), query, {
        preserveState: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};

const setPast = (value) => {
    form.past = value;
    apply();
};
</script>

<template>
    <Head :title="$t('events.title')" />

    <AppLayout>
        <PageHeader :title="$t('events.title')" :lead="$t('events.intro')" :kicker="$t('app.name')">
            <template v-if="can.create" #actions>
                <Button :href="route('events.create')"><Plus aria-hidden="true" />{{ $t('events.new') }}</Button>
            </template>
        </PageHeader>

        <div>
            <div class="mt-6 space-y-6">
                <div class="flex flex-wrap items-end gap-4 border-b border-line pb-6">
                    <div class="flex rounded-full border border-line bg-sunken p-1" role="group">
                        <button
                            v-for="value in [false, true]"
                            :key="String(value)"
                            type="button"
                            class="min-h-10 rounded-full px-4 text-sm font-medium transition-colors duration-200"
                            :aria-pressed="form.past === value"
                            :class="form.past === value ? 'bg-ink text-canvas' : 'text-ink-muted hover:text-ink'"
                            @click="setPast(value)"
                        >
                            {{ value ? $t('events.past') : $t('events.upcoming') }}
                        </button>
                    </div>
                    <label class="min-w-[12rem] flex-1 text-sm font-medium text-ink sm:max-w-xs">
                        {{ $t('events.club') }}
                        <SelectInput v-model="form.club" class="mt-1 block w-full" @change="apply">
                            <option value="">{{ $t('events.all') }}</option>
                            <option value="school">{{ $t('events.school_wide') }}</option>
                            <option v-for="c in clubs" :key="c.slug" :value="c.slug">{{ c.name }}</option>
                        </SelectInput>
                    </label>
                </div>

                <div v-if="loading" class="space-y-3" :aria-label="$t('common.loading')"><Skeleton v-for="n in 3" :key="n" class="h-24" /></div>
                <EmptyState v-else-if="events.data.length === 0" :title="$t('events.empty')" />
                <TransitionGroup v-else tag="div" name="list" class="relative space-y-3">
                    <EventCard v-for="event in events.data" :key="event.id" :event="event" />
                </TransitionGroup>

                <Pagination :links="events.links" />
            </div>
        </div>
    </AppLayout>
</template>
