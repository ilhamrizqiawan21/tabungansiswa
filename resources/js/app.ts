import '../css/app.css';
import '../css/sneat.css';
import '../css/motion.css';
import ActivityFeedback from './Components/ActivityFeedback.vue';
import ConfirmDialog from './Components/ConfirmDialog.vue';
import { installActivityEvents, trackPageTransition } from './activity';
import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    title: title => `${title} — ${import.meta.env.VITE_APP_NAME ?? 'Tabungan Siswa'}`,
    resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    defaults: {
        visitOptions: () => ({ viewTransition: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : trackPageTransition }),
    },
    setup({ el, App, props, plugin }) {
        const cleanup = installActivityEvents();
        const app = createApp({ render: () => h('div', { class: 'app-root' }, [h(App, props), h(ActivityFeedback), h(ConfirmDialog)]) });
        app.use(plugin);
        app.onUnmount(cleanup);
        app.mount(el);
        document.getElementById('app-boot')?.remove();
    },
    progress: false,
});
