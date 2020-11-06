require('./bootstrap')

import Vue from 'vue'


import {
    InertiaApp,
    plugin
} from '@inertiajs/inertia-vue'
import {
    InertiaProgress
} from '@inertiajs/progress'

import PortalVue from 'portal-vue'

InertiaProgress.init({
    delay: 250,

    // The color of the progress bar.
    color: '#29d',

    // Whether to include the default NProgress styles.
    includeCSS: true,

    // Whether the NProgress spinner will be shown.
    showSpinner: true,
})

Vue.mixin({
    methods: {
        route
    }
})
Vue.use(plugin)
Vue.use(PortalVue)

const app = document.getElementById('app')

new Vue({
    render: (h) =>
        h(InertiaApp, {
            props: {
                initialPage: JSON.parse(app.dataset.page),
                resolveComponent: (name) => require(`./Pages/${name}`).default,
            },
        }),
}).$mount(app)
