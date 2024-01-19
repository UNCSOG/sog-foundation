<template>
	<div
		role="tabpanel"
		tabindex="0"
		id="beehive-widget-content--general"
		class="sui-tab-content active"
		aria-labelledby="beehive-widget-tab--general"
	>
		<sui-notice v-if="canGetStats && isEmpty" type="info">
			<p>{{ $i18n.notice.empty_data }}</p>
		</sui-notice>

		<sui-notice v-else-if="!canGetStats && !isConnected" type="error">
			<p v-html="$i18n.notice.auth_required"></p>
		</sui-notice>

		<div class="beehive-buttons-wrapper">
			<top-button
				v-for="(item, key) in summarySections"
				:key="key"
				:name="key"
				:title="item.title"
				:tab="item.tab"
				:stats="stats"
				@tabChange="tabChange"
			/>
			<bottom-button
				v-for="(item, key) in topSections"
				:key="key"
				:name="key"
				:title="item.title"
				:tab="item.tab"
				:stats="stats"
				@tabChange="tabChange"
			/>
		</div>
	</div>
</template>

<script>
import TopButton from './general/top-button'
import BottomButton from './general/bottom-button'
import SuiNotice from '@/components/sui/sui-notice'

export default {
	name: 'GeneralStats',

	props: {
		stats: Object | Array,
	},

	components: {
		SuiNotice,
		TopButton,
		BottomButton,
	},

	data() {
		return {
			summarySections: {
				users: {
					title: this.$i18n.label.users,
					tab: 'audience',
				},
				pageviews: {
					title: this.$i18n.label.pageviews,
					tab: 'audience',
				},
			},

			topSections: {
				page: {
					title: this.$i18n.label.top_page,
					tab: 'pages',
				},
				country: {
					title: this.$i18n.label.top_country,
					tab: 'traffic',
				},
				medium: {
					title: this.$i18n.label.top_referral,
					tab: 'traffic',
				},
				search_engine: {
					title: this.$i18n.label.top_search_engine,
					tab: 'traffic',
				},
			},
		}
	},

	computed: {
		/**
		 * Check if current site can get statistics.
		 *
		 * @since 3.3.0
		 *
		 * @return {boolean}
		 */
		canGetStats() {
			return this.$moduleVars.can_get_stats
		},

		/**
		 * Check if Google account is connected.
		 *
		 * @since 3.3.5
		 *
		 * @return {boolean}
		 */
		isConnected() {
			return this.$store.state.helpers.google.logged_in
		},

		/**
		 * Check if stats are empty from API.
		 *
		 * @since 3.3.5
		 *
		 * @return {boolean}
		 */
		isEmpty() {
			return Object.keys(this.stats).length <= 0
		},
	},

	methods: {
		/**
		 * Open the selected tab.
		 *
		 * @since 3.3.8
		 */
		tabChange(name) {
			// Emit tab change.
			this.$emit('tabChange', name)
		},
	},
}
</script>
