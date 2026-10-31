import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { i18n, t } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || '164 ГПИЕ';

createInertiaApp({
    title: (title) => (title ? `${title} – ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    defaults: {
        // Cross-fade full page navigations; filters, polling and form posts stay instant.
        visitOptions: (href, options) => ({
            viewTransition: (options.method ?? 'get') === 'get' && !options.preserveState && !options.only?.length,
        }),
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n, { translations: props.initialPage.props.translations })
            .mount(el);
    },
    progress: {
        color: '#AA151B',
        delay: 150,
    },
});

export { t };
