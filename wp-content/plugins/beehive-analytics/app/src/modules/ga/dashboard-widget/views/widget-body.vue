<template>
	<div id="beehive-widget-body" class="sui-tabs sui-tabs-overflow">
		<div tabindex="-1" class="sui-tabs-navigation" aria-hidden="true">
			<button
				type="button"
				class="sui-button-icon sui-tabs-navigation--left"
			>
				<i class="sui-icon-chevron-left"></i>
			</button>
			<button
				type="button"
				class="sui-button-icon sui-tabs-navigation--right"
			>
				<i class="sui-icon-chevron-right"></i>
			</button>
		</div>

		<div role="tablist" class="sui-tabs-menu">
			<tab-button
				v-for="(label, name) in tabs"
				:name="name"
				:label="label"
				:default-tab="defaultTab"
			/>
		</div>

		<div class="sui-tabs-content">
			<general-stats
				v-if="canView('general')"
				:stats="stats"
				@tabChange="tabChange"
			/>

			<audience
				v-if="canView('audience')"
				:stats="stats"
				:selected-item="audienceDefault"
				:active-tab="defaultTab"
			/>

			<pages
				v-if="canView('pages')"
				:stats="stats"
				:active-tab="defaultTab"
			/>

			<traffic
				v-if="canView('traffic')"
				:stats="stats"
				:active-tab="defaultTab"
			/>
		</div>
	</div>
</template>

<script>
import Pages from './tabs/pages'
import Traffic from './tabs/traffic'
import Audience from './tabs/audience'
import { canViewStats } from '@/helpers/utils'
import GeneralStats from './tabs/general-stats'
import TabButton from './components/tab-button'

export default {
	name: 'WidgetBody',

	props: ['stats'],

	components: {
		TabButton,
		GeneralStats,
		Audience,
		Pages,
		Traffic,
	},

	data() {
		return {
			audienceDefault: 'sessions',
			tabs: {
				general: this.$i18n.label.general_stats,
				audience: this.$i18n.label.audience,
				pages: this.$i18n.label.top_pages,
				traffic: this.$i18n.label.traffic,
			},
		}
	},

	mounted() {
		const body = jQuery('#beehive-widget-body')
		const navigation = body.find('.sui-tabs-navigation')

		// Initialize tabs.
		SUI.tabs()

		// Initialize overflow tabs.
		navigation.each(function () {
			SUI.tabsOverflow(jQuery(this))
		})
	},

	computed: {
		/**
		 * Get default tab item.
		 *
		 * @since 3.3.8
		 *
		 * @return {string}
		 */
		defaultTab() {
			if (this.canView('general')) {
				return 'general'
			} else if (this.canView('audience')) {
				return 'audience'
			} else if (this.canView('pages')) {
				return 'pages'
			} else {
				return 'traffic'
			}
		},
	},

	methods: {
		/**
		 * Check if current user can view.
		 *
		 * @param {string} type Stats type.
		 *
		 * @since 3.3.6
		 *
		 * @return {string|boolean}
		 */
		canView(type) {
			return canViewStats(type, 'dashboard')
		},

		/**
		 * Process tab change.
		 *
		 * @param {string} tab New tab
		 *
		 * @since 3.3.6
		 */
		tabChange(tab) {
			this.audienceDefault = tab
		},
	},
}
</script>
