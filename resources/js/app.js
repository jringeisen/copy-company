import './bootstrap';
import '../css/app.css';

import { createSSRApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import FlashMessages from './Components/FlashMessages.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Content Platform';

createInertiaApp({
    title: (title) => title.includes(appName) ? title : `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createSSRApp({
            render: () => h('div', [
                h(App, props),
                h(FlashMessages),
            ]),
        })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#6366f1',
    },
});
