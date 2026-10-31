<script setup>
import { ArrowLeft, SendHorizontal, WifiOff } from 'lucide-vue-next';
import Button from '@/Components/ui/Button.vue';
import { fieldClasses } from '@/Components/ui/variants';
import AppLayout from '@/Layouts/AppLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ReportButton from '@/Components/ReportButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { formatDateTime } from '@/format';
import { api } from '@/http';
import { t } from '@/i18n';
import { confirmDialog } from '@/Components/ui/confirm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    channels: { type: Array, required: true },
    channel: { type: Object, default: null },
    messages: { type: Array, default: () => [] },
    hasMore: { type: Boolean, default: false },
    mute: { type: Object, default: null },
    serverTime: { type: String, default: null },
    mutes: { type: Array, default: () => [] },
    muteDurations: { type: Array, default: () => [] },
    pollSeconds: { type: Number, default: 7 },
    maxLength: { type: Number, default: 2000 },
});

const groups = computed(() =>
    ['class', 'grade', 'profile']
        .map((type) => ({ type, items: props.channels.filter((c) => c.type === type) }))
        .filter((group) => group.items.length),
);

const list = ref([...props.messages]);
const more = ref(props.hasMore);
const currentMute = ref(props.mute);
const canPost = ref(props.channel?.can.post ?? false);
const since = ref(props.serverTime);
const body = ref('');
const sending = ref(false);
const loadingMore = ref(false);
const error = ref('');
const offline = ref(false);
const scroller = ref(null);
let timer = null;
let polling = false;

const lastId = () => (list.value.length ? list.value[list.value.length - 1].id : 0);
const charsLeft = computed(() => props.maxLength - body.value.length);

const nearBottom = () => {
    const el = scroller.value;
    return !el || el.scrollHeight - el.scrollTop - el.clientHeight < 120;
};

const scrollToBottom = async () => {
    await nextTick();
    if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight;
};

const append = (messages) => {
    const known = new Set(list.value.map((m) => m.id));
    const fresh = messages.filter((m) => !known.has(m.id));
    if (fresh.length) list.value = [...list.value, ...fresh];
    return fresh.length;
};

const poll = async () => {
    if (!props.channel || polling || document.hidden) return;
    polling = true;
    try {
        const url = route('channels.messages.index', { channel: props.channel.id, after: lastId(), since: since.value });
        const data = await api('GET', url);
        const stick = nearBottom();
        if (data.deleted_ids.length) {
            const removed = new Set(data.deleted_ids);
            list.value = list.value.filter((m) => !removed.has(m.id));
        }
        if (append(data.messages) && stick) scrollToBottom();
        currentMute.value = data.mute;
        canPost.value = data.can_post;
        since.value = data.server_time;
        offline.value = false;
    } catch {
        offline.value = true;
    } finally {
        polling = false;
    }
};

const loadMore = async () => {
    if (!list.value.length || loadingMore.value) return;
    loadingMore.value = true;
    const el = scroller.value;
    const previousHeight = el?.scrollHeight ?? 0;
    try {
        const data = await api('GET', route('channels.messages.index', { channel: props.channel.id, before: list.value[0].id }));
        list.value = [...data.messages, ...list.value];
        more.value = data.hasMore;
        await nextTick();
        if (el) el.scrollTop = el.scrollHeight - previousHeight;
    } catch {
        error.value = t('common.error');
    } finally {
        loadingMore.value = false;
    }
};

const send = async () => {
    const text = body.value.trim();
    if (!text || sending.value || !canPost.value) return;
    sending.value = true;
    error.value = '';
    try {
        const data = await api('POST', route('channels.messages.store', props.channel.id), { body: text });
        append([data.message]);
        body.value = '';
        scrollToBottom();
    } catch (e) {
        if (e.status === 429) error.value = t('channels.rate_limited');
        else if (e.status === 422) error.value = e.data.errors?.body?.[0] ?? t('channels.send_failed');
        else {
            error.value = t('channels.send_failed');
            if (e.status === 403) poll();
        }
    } finally {
        sending.value = false;
    }
};

const onKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
        event.preventDefault();
        send();
    }
};

const destroy = async (message) => {
    if (!await confirmDialog(t('common.confirm_delete'))) return;
    try {
        await api('DELETE', route('messages.destroy', message.id));
        list.value = list.value.filter((m) => m.id !== message.id);
    } catch {
        error.value = t('common.error');
    }
};

// Moderation: mute dialog
const muteTarget = ref(null);
const muteForm = useForm({ user_id: null, minutes: props.muteDurations[0] ?? 15, reason: '', global: false });

const openMute = (message) => {
    muteTarget.value = message;
    muteForm.reset();
    muteForm.clearErrors();
    muteForm.user_id = message.author_id;
};

const submitMute = () => {
    muteForm.post(route('channels.mutes.store', props.channel.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (muteTarget.value = null),
    });
};

const unmute = (mute) => router.delete(route('mutes.destroy', mute.id), { preserveScroll: true, preserveState: true });

onMounted(() => {
    if (!props.channel) return;
    scrollToBottom();
    timer = setInterval(poll, Math.max(3, props.pollSeconds) * 1000);
    document.addEventListener('visibilitychange', poll);
});

onBeforeUnmount(() => {
    clearInterval(timer);
    document.removeEventListener('visibilitychange', poll);
});
</script>

<template>
    <Head :title="channel ? channel.name : $t('channels.title')" />

    <AppLayout>
        <div class="-mx-4 -my-8 sm:mx-0 sm:my-0">
            <div class="flex overflow-hidden border border-line bg-surface sm:rounded" style="height: calc(100dvh - 10rem)">
                <aside class="w-full shrink-0 overflow-y-auto border-line bg-sunken/50 md:block md:w-72 md:border-e" :class="channel ? 'hidden' : 'block'">
                    <h2 class="px-4 pb-3 pt-5 font-display text-title-3 font-semibold text-ink">{{ $t('channels.list') }}</h2>
                    <p v-if="channels.length === 0" class="px-4 text-sm text-ink-subtle">{{ $t('channels.empty_list') }}</p>
                    <nav v-for="group in groups" :key="group.type" class="pb-3">
                        <p class="kicker px-4 py-2">{{ $t(`channels.types.${group.type}`) }}</p>
                        <Link
                            v-for="item in group.items"
                            :key="item.id"
                            :href="route('channels.show', item.id)"
                            class="relative mx-2 flex min-h-11 items-center justify-between rounded px-3 text-sm transition-colors hover:bg-sunken"
                            :class="channel?.id === item.id ? 'bg-surface font-semibold text-ink shadow-hairline before:absolute before:inset-y-2 before:left-0 before:w-0.5 before:rounded-full before:bg-primary' : 'text-ink-muted'"
                            :aria-current="channel?.id === item.id ? 'page' : undefined"
                        >
                            <span class="truncate"><span class="text-ink-subtle" aria-hidden="true">#</span> {{ item.name }}</span>
                            <span v-if="item.is_archived" class="ms-2 text-xs text-ink-subtle">{{ $t('channels.archived') }}</span>
                        </Link>
                    </nav>
                </aside>

                <section v-if="!channel" class="hidden flex-1 items-center justify-center md:flex">
                    <EmptyState :title="$t('channels.title')" :hint="$t('channels.choose')" />
                </section>

                <section v-else class="flex min-w-0 flex-1 flex-col">
                    <header class="flex min-h-16 items-center gap-3 border-b border-line px-4 py-2">
                        <Link :href="route('channels.index')" class="-ms-2 inline-flex size-11 items-center justify-center rounded text-ink-muted hover:bg-sunken md:hidden" :aria-label="$t('channels.back_to_list')"><ArrowLeft class="size-5" aria-hidden="true" /></Link>
                        <h1 class="truncate font-display text-xl font-semibold text-ink"><span class="text-primary-ink" aria-hidden="true">#</span> {{ channel.name }}</h1>
                        <span v-if="offline" class="ms-auto inline-flex items-center gap-1.5 rounded-full bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent-ink" role="status"><WifiOff class="size-3.5" aria-hidden="true" />{{ $t('channels.connection_lost') }}</span>
                    </header>

                    <div v-if="channel.can.moderate && mutes.length" class="border-b border-line bg-sunken/60 px-4 py-2 text-xs text-ink-muted">
                        <span class="font-semibold">{{ $t('channels.active_mutes') }}:</span>
                        <span v-for="m in mutes" :key="m.id" class="ms-2 inline-flex items-center gap-1">
                            {{ m.user }} ({{ $t('channels.until', { time: formatDateTime(m.until) }) }}<template v-if="m.global">, {{ $t('channels.mute_global') }}</template>)
                            <button type="button" class="text-primary-ink hover:underline" @click="unmute(m)">{{ $t('channels.unmute') }}</button>
                        </span>
                    </div>

                    <div ref="scroller" class="flex-1 space-y-3 overflow-y-auto overscroll-contain px-4 py-5" role="log" :aria-label="channel.name">
                        <div v-if="more" class="text-center">
                            <button type="button" :disabled="loadingMore" class="min-h-11 rounded-full border border-line px-4 text-sm text-ink-muted hover:bg-sunken hover:text-ink disabled:opacity-50" @click="loadMore">
                                {{ loadingMore ? $t('common.loading') : $t('channels.load_more') }}
                            </button>
                        </div>
                        <p v-if="list.length === 0" class="py-10 text-center text-sm text-ink-subtle">{{ $t('channels.no_messages') }}</p>

                        <div v-for="message in list" :key="message.id" class="group flex" :class="message.own ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[85%] px-3.5 py-2.5 sm:max-w-[70%]" :class="message.own ? 'rounded-2xl rounded-br-sm bg-primary text-on-primary' : 'rounded-2xl rounded-bl-sm border border-line bg-surface text-ink'">
                                <p v-if="!message.own" class="mb-0.5 text-xs font-semibold text-primary-ink">{{ message.author ?? $t('common.deleted_user') }}</p>
                                <p class="whitespace-pre-line break-words text-[0.9375rem] leading-relaxed">{{ message.body }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs" :class="message.own ? 'text-on-primary/85' : 'text-ink-subtle'">
                                    <span>{{ formatDateTime(message.created_at) }}</span>
                                    <button v-if="message.can.delete" type="button" class="hover:underline" @click="destroy(message)">{{ $t('common.delete') }}</button>
                                    <button v-if="message.can.mute" type="button" class="hover:underline" @click="openMute(message)">{{ $t('channels.mute') }}</button>
                                    <ReportButton v-if="message.can.report" type="message" :id="message.id" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <footer class="border-t border-line bg-surface p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                        <p v-if="channel.is_archived" class="text-sm text-ink-subtle">{{ $t('channels.archived_notice') }}</p>
                        <div v-else-if="currentMute" class="rounded bg-accent-soft p-3 text-sm text-accent-ink" role="status">
                            {{ $t('channels.muted_notice', { until: formatDateTime(currentMute.until) }) }}
                            <span v-if="currentMute.reason"> ({{ currentMute.reason }})</span>
                            <span v-if="currentMute.global" class="block text-xs">{{ $t('channels.muted_global') }}</span>
                        </div>
                        <form v-else-if="canPost" class="flex items-end gap-2" @submit.prevent="send">
                            <div class="flex-1">
                                <label for="message-body" class="sr-only">{{ $t('channels.message') }}</label>
                                <textarea
                                    id="message-body"
                                    v-model="body"
                                    rows="2"
                                    :maxlength="maxLength"
                                    :class="[fieldClasses, 'resize-none py-2.5']"
                                    :placeholder="$t('channels.placeholder')"
                                    @keydown="onKeydown"
                                />
                                <div class="mt-1 flex justify-between text-xs">
                                    <InputError :message="error" />
                                    <span v-if="charsLeft < 200" class="ms-auto text-ink-subtle">{{ $t('channels.chars_left', { count: charsLeft }) }}</span>
                                </div>
                            </div>
                            <Button type="submit" class="mb-5 max-sm:size-11 max-sm:px-0" :disabled="!body.trim()" :loading="sending" :aria-label="$t('channels.send')"><SendHorizontal aria-hidden="true" /><span class="max-sm:sr-only">{{ sending ? $t('channels.sending') : $t('channels.send') }}</span></Button>
                        </form>
                    </footer>
                </section>
            </div>
        </div>

        <Modal :show="muteTarget !== null" max-width="md" @close="muteTarget = null">
            <form v-if="muteTarget" class="space-y-4 p-6" @submit.prevent="submitMute">
                <h3 class="font-display text-title-3 font-semibold text-ink">{{ $t('channels.mute_title', { name: muteTarget.author }) }}</h3>
                <label class="block text-sm text-ink-muted">
                    {{ $t('channels.mute_duration') }}
                    <SelectInput v-model="muteForm.minutes" class="mt-1 block w-full">
                        <option v-for="d in muteDurations" :key="d" :value="d">{{ $t(`channels.durations.${d}`) }}</option>
                    </SelectInput>
                </label>
                <label class="block text-sm text-ink-muted">
                    {{ $t('channels.mute_reason') }}
                    <input v-model="muteForm.reason" type="text" maxlength="500" :class="[fieldClasses, 'mt-1']" />
                </label>
                <label class="flex items-center gap-2 text-sm text-ink-muted">
                    <Checkbox v-model:checked="muteForm.global" />
                    {{ $t('channels.mute_global') }}
                </label>
                <InputError :message="muteForm.errors.user_id || muteForm.errors.minutes || muteForm.errors.reason" />
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="muteTarget = null">{{ $t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="muteForm.processing">{{ $t('channels.mute') }}</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
