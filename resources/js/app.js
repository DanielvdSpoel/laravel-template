import './bootstrap';

import {createInertiaApp} from "@inertiajs/vue3";

import { createApp, h } from 'vue'
import { createI18n } from 'vue-i18n'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        const messages = JSON.parse(props.initialPage.props.translations);

        const i18n = createI18n({
            locale: 'nl',
            fallbackLocale: 'en',
            globalInjection: true,
            messages,
        })

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .mixin({ methods: { route } })
            .mount(el)
    },
})
