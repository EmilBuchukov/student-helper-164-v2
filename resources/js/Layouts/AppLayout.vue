<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { BookOpenText, CalendarDays, ChevronDown, Home, LayoutDashboard, LogOut, Menu, MessageCircleQuestion, MessagesSquare, Shield, UserRound, UsersRound, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import ConfirmHost from '@/Components/ui/ConfirmHost.vue';
import DropdownItem from '@/Components/ui/DropdownItem.vue';
import DropdownMenu from '@/Components/ui/DropdownMenu.vue';
import ThemeToggle from '@/Components/ui/ThemeToggle.vue';
import Toaster from '@/Components/ui/Toaster.vue';
import Wordmark from '@/Components/ui/Wordmark.vue';
import { toast } from '@/Components/ui/toast';
import { adminNav, mainNav } from '@/navigation';

defineProps({ wide: { type: Boolean, default: false } });

const page = usePage();
const user = computed(() => page.props.auth.user);
const menuOpen = ref(false);
const visibleAdminNav = computed(() => adminNav.filter((item) => (item.roles ?? ['admin']).includes(user.value?.role)));
const visibleNav = computed(() => mainNav.filter((item) => !item.hideFor?.includes(user.value?.role)));
const isActive = (item) => route().current(item.active ?? item.route);
const adminActive = computed(() => visibleAdminNav.value.some(isActive));
const icons = {
    home: Home,
    'exams.index': BookOpenText,
    'questions.index': MessageCircleQuestion,
    'channels.index': MessagesSquare,
    'clubs.index': UsersRound,
    'events.index': CalendarDays,
};
const tabNav = computed(() => visibleNav.value.filter((item) => item.route !== 'home'));

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast({ message: flash.success, tone: 'success' });
        if (flash?.error) toast({ message: flash.error, tone: 'danger' });
    },
    { immediate: true },
);
watch(() => page.url, () => (menuOpen.value = false));
</script>

<template>
    <div class="flex min-h-screen flex-col bg-canvas" :class="user ? 'pb-[calc(4rem+env(safe-area-inset-bottom))] md:pb-0' : ''">
        <a href="#main" class="sr-only z-[70] rounded bg-ink px-4 py-2 text-canvas focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ $t('a11y.skip_to_content') }}</a>

        <header class="sticky top-0 z-40 border-b border-line bg-canvas/90 backdrop-blur supports-[backdrop-filter]:bg-canvas/80">
            <div class="rojigualda h-1" aria-hidden="true" />
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
                <Link :href="route('home')" class="-m-1 rounded p-1">
                    <Wordmark />
                </Link>

                <nav v-if="user" :aria-label="$t('a11y.main_nav')" class="hidden h-full items-stretch lg:flex">
                    <Link
                        v-for="item in visibleNav"
                        :key="item.route"
                        :href="route(item.route)"
                        :aria-current="isActive(item) ? 'page' : undefined"
                        :class="[
                            'relative flex items-center px-3 text-[0.9375rem] font-medium transition-colors after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:origin-left after:bg-primary after:transition-transform after:duration-200',
                            isActive(item) ? 'text-ink after:scale-x-100' : 'text-ink-muted after:scale-x-0 hover:text-ink',
                        ]"
                    >
                        {{ $t(item.label) }}
                    </Link>
                </nav>

                <div class="ms-auto flex items-center gap-2">
                    <ThemeToggle class="hidden sm:inline-flex" />

                    <template v-if="user">
                        <DropdownMenu v-if="visibleAdminNav.length" width="w-64">
                            <template #trigger>
                                <button
                                    type="button"
                                    :class="['hidden h-10 items-center gap-1.5 rounded px-3 text-sm font-medium transition-colors md:inline-flex', adminActive ? 'bg-primary-soft text-primary-ink' : 'text-ink-muted hover:bg-sunken hover:text-ink']"
                                >
                                    <Shield class="size-4" aria-hidden="true" />
                                    {{ $t('nav.admin') }}
                                    <ChevronDown class="size-4" aria-hidden="true" />
                                </button>
                            </template>
                            <DropdownItem v-for="item in visibleAdminNav" :key="item.route" :href="route(item.route)">
                                {{ $t(item.label) }}
                            </DropdownItem>
                        </DropdownMenu>

                        <DropdownMenu>
                            <template #trigger>
                                <button type="button" class="hidden items-center gap-2 rounded-full p-0.5 pe-2 transition-colors hover:bg-sunken md:inline-flex" :aria-label="$t('a11y.account_menu')">
                                    <Avatar :name="user.name" size="sm" />
                                    <ChevronDown class="size-4 text-ink-subtle" aria-hidden="true" />
                                </button>
                            </template>
                            <div class="px-3 pb-2 pt-1.5">
                                <p class="truncate font-medium text-ink">{{ user.name }}</p>
                                <p class="truncate text-sm text-ink-subtle">{{ user.email }}</p>
                            </div>
                            <div class="my-1 h-px bg-line" />
                            <DropdownItem :href="route('dashboard')"><LayoutDashboard aria-hidden="true" />{{ $t('nav.dashboard') }}</DropdownItem>
                            <DropdownItem :href="route('profile.edit')"><UserRound aria-hidden="true" />{{ $t('nav.profile') }}</DropdownItem>
                            <div class="my-1 h-px bg-line" />
                            <DropdownItem :href="route('logout')" method="post"><LogOut aria-hidden="true" />{{ $t('nav.logout') }}</DropdownItem>
                        </DropdownMenu>

                        <DialogRoot v-model:open="menuOpen">
                            <button type="button" class="inline-flex size-11 items-center justify-center rounded text-ink hover:bg-sunken lg:hidden" :aria-label="$t('nav.open_menu')" @click="menuOpen = true">
                                <Menu class="size-6" aria-hidden="true" />
                            </button>
                            <DialogPortal>
                                <DialogOverlay class="fixed inset-0 z-50 bg-sand-950/50 data-[state=open]:animate-fade-in" />
                                <DialogContent class="fixed inset-y-0 left-0 z-50 flex w-[min(22rem,88vw)] flex-col overflow-y-auto border-r border-line bg-canvas shadow-pop focus:outline-none data-[state=open]:animate-sheet-in" :aria-describedby="undefined">
                                    <div class="rojigualda h-1" aria-hidden="true" />
                                    <div class="flex items-center justify-between px-4 py-3">
                                        <DialogTitle as="div"><Wordmark compact /></DialogTitle>
                                        <button type="button" class="inline-flex size-11 items-center justify-center rounded text-ink-muted hover:bg-sunken" :aria-label="$t('nav.close_menu')" @click="menuOpen = false">
                                            <X class="size-6" aria-hidden="true" />
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-3 border-y border-line px-4 py-4">
                                        <Avatar :name="user.name" />
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-ink">{{ user.name }}</p>
                                            <p class="truncate text-sm text-ink-subtle">{{ user.email }}</p>
                                        </div>
                                    </div>
                                    <nav :aria-label="$t('a11y.main_nav')" class="flex flex-col p-2">
                                        <Link
                                            v-for="item in visibleNav"
                                            :key="item.route"
                                            :href="route(item.route)"
                                            :aria-current="isActive(item) ? 'page' : undefined"
                                            :class="['flex min-h-12 items-center gap-3 rounded px-3 font-medium', isActive(item) ? 'bg-primary-soft text-primary-ink' : 'text-ink hover:bg-sunken']"
                                        >
                                            <component :is="icons[item.route]" class="size-5" aria-hidden="true" />
                                            {{ $t(item.label) }}
                                        </Link>
                                    </nav>
                                    <nav v-if="visibleAdminNav.length" :aria-label="$t('a11y.admin_nav')" class="border-t border-line p-2">
                                        <p class="kicker px-3 py-2">{{ $t('nav.admin') }}</p>
                                        <Link
                                            v-for="item in visibleAdminNav"
                                            :key="item.route"
                                            :href="route(item.route)"
                                            :aria-current="isActive(item) ? 'page' : undefined"
                                            :class="['flex min-h-11 items-center rounded px-3 text-[0.9375rem]', isActive(item) ? 'bg-primary-soft font-medium text-primary-ink' : 'text-ink-muted hover:bg-sunken hover:text-ink']"
                                        >
                                            {{ $t(item.label) }}
                                        </Link>
                                    </nav>
                                    <div class="mt-auto flex flex-col gap-1 border-t border-line p-2">
                                        <Link :href="route('dashboard')" class="flex min-h-11 items-center gap-3 rounded px-3 text-ink hover:bg-sunken"><LayoutDashboard class="size-5 text-ink-subtle" aria-hidden="true" />{{ $t('nav.dashboard') }}</Link>
                                        <Link :href="route('profile.edit')" class="flex min-h-11 items-center gap-3 rounded px-3 text-ink hover:bg-sunken"><UserRound class="size-5 text-ink-subtle" aria-hidden="true" />{{ $t('nav.profile') }}</Link>
                                        <Link :href="route('logout')" method="post" as="button" class="flex min-h-11 items-center gap-3 rounded px-3 text-left text-ink hover:bg-sunken"><LogOut class="size-5 text-ink-subtle" aria-hidden="true" />{{ $t('nav.logout') }}</Link>
                                        <div class="flex items-center justify-between px-3 py-2">
                                            <span class="text-sm text-ink-muted">{{ $t('theme.label') }}</span>
                                            <ThemeToggle />
                                        </div>
                                    </div>
                                </DialogContent>
                            </DialogPortal>
                        </DialogRoot>
                    </template>

                    <template v-else>
                        <Link :href="route('login')" class="inline-flex h-10 items-center rounded px-3 text-sm font-medium text-ink hover:bg-sunken">{{ $t('nav.login') }}</Link>
                        <Link :href="route('register')" class="inline-flex h-10 items-center rounded bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">{{ $t('nav.register') }}</Link>
                    </template>
                </div>
            </div>
        </header>

        <main id="main" tabindex="-1" class="page-enter flex-1 focus:outline-none">
            <div v-if="$slots.header" class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
            <div :class="wide ? '' : 'mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8'">
                <slot />
            </div>
        </main>

        <SiteFooter />

        <!-- Phone tab bar -->
        <nav v-if="user" :aria-label="$t('a11y.main_nav')" class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-canvas/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden">
            <ul class="mx-auto flex max-w-lg">
                <li v-for="item in tabNav" :key="item.route" class="flex-1">
                    <Link
                        :href="route(item.route)"
                        :aria-current="isActive(item) ? 'page' : undefined"
                        :class="['relative flex h-16 flex-col items-center justify-center gap-1 truncate px-0.5 text-[0.625rem] font-medium min-[400px]:text-[0.6875rem]', isActive(item) ? 'text-primary-ink' : 'text-ink-subtle']"
                    >
                        <span v-if="isActive(item)" class="absolute inset-x-5 top-0 h-0.5 bg-primary" aria-hidden="true" />
                        <component :is="icons[item.route]" class="size-5" :stroke-width="isActive(item) ? 2.25 : 1.75" aria-hidden="true" />
                        {{ $t(item.label) }}
                    </Link>
                </li>
            </ul>
        </nav>

        <Toaster />
        <ConfirmHost />
    </div>
</template>
