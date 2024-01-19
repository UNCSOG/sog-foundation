import Vue from 'vue'
import App from './app'
import { VuePlugin } from 'vuera'
import Fragment from 'vue-fragment'
import { sprintf } from 'sprintf-js'

Vue.config.productionTip = false

// Global functions.
Vue.mixin({
	methods: { sprintf },
})

// Global vars.
Vue.prototype.$i18n = window.beehiveI18n
Vue.prototype.$vars = window.beehiveVars
Vue.prototype.$moduleVars = window.beehiveModuleVars

Vue.use(VuePlugin)
Vue.use(Fragment.Plugin)

new Vue({
	render: (h) => h(App),
}).$mount('#beehive-tutorials-app')
