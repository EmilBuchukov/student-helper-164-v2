<script setup>
import { Link } from '@inertiajs/vue3';
import Azulejo from '@/Components/ui/Azulejo.vue';
import ThemeToggle from '@/Components/ui/ThemeToggle.vue';
import Wordmark from '@/Components/ui/Wordmark.vue';

defineProps({
    title: { type: String, default: null },
    intro: { type: String, default: null },
});
</script>

<template>
    <div class="grid min-h-screen bg-canvas lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        <aside class="relative hidden overflow-hidden bg-sand-950 text-sand-50 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="absolute inset-0 opacity-[0.14]"><Azulejo /></div>
            <div class="rojigualda absolute inset-y-0 right-0 w-1.5" aria-hidden="true" />
            <Link :href="route('home')" class="relative -m-1 self-start rounded p-1 [&_.text-ink]:text-sand-50 [&_.text-ink-subtle]:text-sand-300">
                <Wordmark />
            </Link>
            <figure class="relative max-w-md">
                <blockquote class="font-display text-title-2 font-medium italic leading-snug text-sand-50">{{ $t('welcome.quote') }}</blockquote>
                <figcaption class="mt-4 flex items-center gap-3 text-sm text-sand-300">
                    <span class="h-0.5 w-6 bg-gold-400" aria-hidden="true" />{{ $t('welcome.quote_author') }}
                </figcaption>
            </figure>
            <p class="relative text-sm text-sand-300">{{ $t('app.full_name') }}</p>
        </aside>

        <div class="flex min-h-screen flex-col">
            <div class="rojigualda h-1 lg:hidden" aria-hidden="true" />
            <header class="flex items-center justify-between px-5 py-4 sm:px-8">
                <Link :href="route('home')" class="-m-1 rounded p-1 lg:invisible"><Wordmark /></Link>
                <ThemeToggle />
            </header>
            <main id="main" class="page-enter flex flex-1 items-start justify-center px-5 pb-12 pt-6 sm:items-center sm:px-8">
                <div class="w-full max-w-[26rem]">
                    <h1 v-if="title" class="font-display text-title-1 font-semibold text-ink">{{ title }}</h1>
                    <p v-if="intro" class="mt-3 text-ink-muted">{{ intro }}</p>
                    <div class="mt-8"><slot /></div>
                </div>
            </main>
            <footer class="px-5 py-6 text-sm text-ink-subtle sm:px-8">{{ $t('footer.about') }}</footer>
        </div>
    </div>
</template>
