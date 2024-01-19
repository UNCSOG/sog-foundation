import Vue from 'vue'
import Router from 'vue-router'
import Account from './tabs/account'
import Settings from './tabs/settings'
import Statistics from './tabs/statistics'
import { hasStatisticsAccess, hasSettingsAccess } from '@/helpers/utils'

Vue.use(Router)

let routes = []

if (hasSettingsAccess()) {
	routes.push({
		path: '/account',
		name: 'Account',
		component: Account,
	})

	routes.push({
		path: '/settings',
		name: 'Settings',
		component: Settings,
	})
}

// Statistics menu is required only when has access.
if (hasStatisticsAccess()) {
	routes.push({
		path: '/statistics',
		name: 'Statistics',
		component: Statistics,
	})

	routes.push({
		path: '*',
		redirect: '/statistics',
	})
} else {
	// Otherwise account should be default.
	routes.push({
		path: '*',
		redirect: '/account',
	})
}

export default new Router({
	linkActiveClass: 'current',
	routes: routes,
})
