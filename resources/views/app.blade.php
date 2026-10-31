<!DOCTYPE html>
<html lang="bg">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="description" content="{{ __('ui.app.tagline') }}">
        <meta name="color-scheme" content="light dark">
        <meta name="theme-color" content="#FBF6EC" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#17120F" media="(prefers-color-scheme: dark)">
        <meta property="og:title" content="{{ __('ui.app.full_name') }}">
        <meta property="og:description" content="{{ __('ui.app.tagline') }}">
        <meta property="og:locale" content="bg_BG">

        <title inertia>{{ config('app.name', '164 ГПИЕ') }}</title>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="alternate icon" type="image/png" href="/favicon.png">

        <script>
            (function () {
                var stored = null;
                try { stored = localStorage.getItem('theme'); } catch (e) {}
                var dark = stored === 'dark' || ((!stored || stored === 'system') && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
