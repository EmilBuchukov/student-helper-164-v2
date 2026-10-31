<script setup>
import { router } from '@inertiajs/vue3';
import { ArrowBigUp } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({
    url: { type: String, required: true },
    count: { type: Number, required: true },
    voted: { type: Boolean, default: false },
    canVote: { type: Boolean, default: false },
});

// Optimistic: flip immediately, server response (or failure) restores the truth.
const busy = ref(false);
const localVoted = ref(props.voted);
const localCount = ref(props.count);
watch(() => [props.voted, props.count], ([voted, count]) => {
    localVoted.value = voted;
    localCount.value = count;
});

const toggle = () => {
    if (!props.canVote || busy.value) return;
    localCount.value += localVoted.value ? -1 : 1;
    localVoted.value = !localVoted.value;
    router.post(props.url, {}, {
        preserveScroll: true,
        onStart: () => (busy.value = true),
        onError: () => {
            localVoted.value = props.voted;
            localCount.value = props.count;
        },
        onFinish: () => (busy.value = false),
    });
};
</script>

<template>
    <button
        type="button"
        class="group flex min-h-14 w-12 shrink-0 flex-col items-center justify-center rounded border text-sm transition duration-150 active:translate-y-px disabled:cursor-default"
        :class="localVoted ? 'border-primary/40 bg-primary-soft text-primary-ink' : 'border-line bg-surface text-ink-muted enabled:hover:border-line-strong enabled:hover:text-ink'"
        :disabled="!canVote"
        :title="canVote ? (localVoted ? $t('votes.remove') : $t('votes.upvote')) : $t('votes.own')"
        :aria-label="canVote ? (localVoted ? $t('votes.remove') : $t('votes.upvote')) : $t('votes.own')"
        :aria-pressed="localVoted"
        @click="toggle"
    >
        <ArrowBigUp class="size-5 transition-transform duration-200 group-enabled:group-hover:-translate-y-0.5" :fill="localVoted ? 'currentColor' : 'none'" aria-hidden="true" />
        <span class="font-semibold tabular-nums">{{ localCount }}</span>
    </button>
</template>
