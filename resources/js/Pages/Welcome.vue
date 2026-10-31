<script setup>
import { Head } from '@inertiajs/vue3';
import { ArrowRight, BookOpenText, MessageCircleQuestion, UsersRound } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Azulejo from '@/Components/ui/Azulejo.vue';
import Button from '@/Components/ui/Button.vue';
import Kicker from '@/Components/ui/Kicker.vue';

const features = [
    { title: 'nav.exams', text: 'welcome.feature_exams', icon: BookOpenText },
    { title: 'nav.questions', text: 'welcome.feature_questions', icon: MessageCircleQuestion },
    { title: 'nav.clubs', text: 'welcome.feature_clubs', icon: UsersRound },
];
</script>

<template>
    <Head :title="$t('welcome.title')" />

    <AppLayout wide>
        <section class="mx-auto grid max-w-7xl gap-10 px-4 pb-16 pt-10 sm:px-6 sm:pt-16 lg:grid-cols-12 lg:gap-12 lg:px-8 lg:pb-24 lg:pt-24">
            <div class="lg:col-span-7">
                <Kicker>{{ $t('app.full_name') }}</Kicker>
                <h1 class="mt-5 font-display text-display font-semibold text-ink">{{ $t('welcome.heading') }}</h1>
                <p class="mt-6 max-w-[52ch] text-lg leading-relaxed text-ink-muted sm:text-xl">{{ $t('welcome.lead') }}</p>

                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <Button v-if="$page.props.auth.user" :href="route('dashboard')" size="lg">
                        {{ $t('welcome.cta_dashboard') }}<ArrowRight aria-hidden="true" />
                    </Button>
                    <template v-else>
                        <Button :href="route('register')" size="lg">{{ $t('welcome.cta_register') }}<ArrowRight aria-hidden="true" /></Button>
                        <Button :href="route('login')" variant="secondary" size="lg">{{ $t('welcome.cta_login') }}</Button>
                    </template>
                </div>
                <p v-if="!$page.props.auth.user" class="mt-4 max-w-[52ch] text-sm text-ink-subtle">{{ $t('welcome.whitelist_note') }}</p>
            </div>

            <figure class="relative isolate flex min-h-[18rem] flex-col justify-end overflow-hidden rounded bg-primary p-7 text-white sm:p-9 lg:col-span-5 lg:min-h-0 dark:bg-red-800">
                <div class="absolute inset-0 -z-10 opacity-25"><Azulejo /></div>
                <span class="absolute right-6 top-4 font-display text-[7rem] font-bold leading-none text-gold-400/90 sm:text-[9rem]" aria-hidden="true">164</span>
                <blockquote class="max-w-sm font-display text-title-3 font-medium italic leading-snug">{{ $t('welcome.quote') }}</blockquote>
                <figcaption class="mt-4 flex items-center gap-3 text-sm text-red-100">
                    <span class="h-0.5 w-6 bg-gold-400" aria-hidden="true" />{{ $t('welcome.quote_author') }}
                </figcaption>
            </figure>
        </section>

        <section class="border-t border-line bg-surface/60">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
                <h2 class="font-display text-title-2 font-semibold text-ink">{{ $t('welcome.features_title') }}</h2>
                <ol class="mt-10 grid gap-x-10 gap-y-10 md:grid-cols-3">
                    <li v-for="(feature, index) in features" :key="feature.title" class="border-t-2 border-ink pt-5">
                        <div class="flex items-baseline justify-between">
                            <span class="font-display text-5xl font-semibold tabular-nums text-primary-ink">0{{ index + 1 }}</span>
                            <component :is="feature.icon" class="size-6 text-ink-subtle" aria-hidden="true" />
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-ink">{{ $t(feature.title) }}</h3>
                        <p class="mt-2 text-ink-muted">{{ $t(feature.text) }}</p>
                    </li>
                </ol>
            </div>
        </section>
    </AppLayout>
</template>
