<template>
	<sui-box
		title-icon="page-multiple"
		aria-live="polite"
		:title="$i18n.label.top_pages"
		:loading="loading"
	>
		<template v-slot:body>
			<p class="beehive-loading-text" v-if="loading">
				<span
					class="sui-icon-loader sui-loading"
					aria-hidden="true"
				></span>
				{{ $i18n.label.fetching_data }}
			</p>

			<p
				class="sui-description"
				v-if="(!isConnected && !canGetStats) || isEmpty"
			>
				{{ $i18n.label.no_information }}
			</p>

			<!-- Render list table -->
			<list-table :stats="stats" v-else />
		</template>
	</sui-box>
</template>

<script>
import ListTable from './pages/list-table'
import SuiBox from '@/components/sui/sui-box'

export default {
	name: 'TopPages',

	components: {
		SuiBox,
		ListTable,
	},

	props: {
		stats: Object,
		loading: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
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
			return Object.keys(this.stats).length <= 0 || !this.stats.pages
		},

		/**
		 * Check if we can show stats.
		 *
		 * @since 3.3.8
		 *
		 * @return {*}
		 */
		canGetStats() {
			return this.$moduleVars.can_get_stats > 0
		},
	},
}
</script>
