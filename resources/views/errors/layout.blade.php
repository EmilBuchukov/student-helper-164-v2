<!DOCTYPE html>
<html lang="bg">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <title>@yield('code') · {{ __('ui.app.name') }}</title>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <script>
            (function () {
                var stored = null;
                try { stored = localStorage.getItem('theme'); } catch (e) {}
                var dark = stored === 'dark' || ((!stored || stored === 'system') && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-canvas font-sans text-ink antialiased">
        <div class="rojigualda h-1" aria-hidden="true"></div>
        <main class="mx-auto grid min-h-[calc(100dvh-0.25rem)] max-w-6xl items-center gap-10 px-4 py-12 sm:px-6 md:grid-cols-12 lg:px-8">
            <div class="md:col-span-7">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                    <img src="/images/logo.png" alt="" class="size-10 rounded-full" width="40" height="40">
                    <span class="font-display text-xl font-semibold">{{ __('ui.app.name') }}</span>
                </a>
                <div class="mt-12"><p class="kicker">{{ __('ui.errors.kicker', ['code' => trim($__env->yieldContent('code'))]) }}</p></div>
                <h1 class="mt-4 font-display text-title-1 font-semibold">@yield('message')</h1>
                <p class="mt-4 max-w-prose text-lg text-ink-muted">@yield('description')</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center rounded bg-primary px-5 text-sm font-semibold text-on-primary transition hover:bg-primary-hover active:scale-[0.98]">{{ __('ui.nav.home') }}</a>
                    @yield('actions')
                </div>
            </div>
            <div class="md:col-span-5" aria-hidden="true">
                <p class="font-display text-[clamp(7rem,4rem+14vw,14rem)] font-semibold leading-none tracking-tight text-primary">@yield('code')</p>
                <figure class="mt-6 border-t border-line pt-4">
                    <blockquote class="font-display text-lg italic text-ink-muted">{{ __('ui.errors.quote') }}</blockquote>
                    <figcaption class="mt-2 text-sm text-ink-subtle">— {{ __('ui.errors.quote_author') }}</figcaption>
                </figure>
            </div>
        </main>
    </body>
</html>
