<template>
	<sui-box
		title-icon="profile-male"
		aria-live="polite"
		:title="$i18n.label.visitors"
		:loading="loading"
	>
		<template v-slot:body>
			<!-- Empty stats notice -->
			<sui-notice v-if="canGetStats && isEmpty" type="info">
				<p>{{ $i18n.notice.google_no_data }}</p>
			</sui-notice>

			<!-- Auth notice -->
			<sui-notice v-else-if="!canGetStats && !isConnected" type="error">
				<p
					v-if="canShowSettingsMenu"
					v-html="
						sprintf(
							$i18n.notice.google_not_linked,
							$vars.urls.accounts
						)
					"
				></p>
				<p v-else v-html="$i18n.notice.google_not_linked_simple"></p>
			</sui-notice>
		</template>

		<template v-slot:outside>
			<!-- Loader animation -->
			<p class="beehive-loading-text" v-if="loading">
				<span
					class="sui-icon-loader sui-loading"
					aria-hidden="true"
				></span>
				{{ $i18n.label.fetching_data }}
			</p>

			<visitors-chart
				:stats="stats"
				:compare="compare"
				:periods="periods"
			/>
		</template>
	</sui-box>
</template>

<script>
import SuiBox from '@/components/sui/sui-box'
import { hasSettingsAccess } from '@/helpers/utils'
import SuiNotice from '@/components/sui/sui-notice'
import VisitorsChart from './visitors/visitors-chart'

export default {
	name: 'Visitors',

	components: {
		SuiBox,
		SuiNotice,
		VisitorsChart,
	},

	props: {
		stats: Object,
		periods: Object,
		loading: {
			type: Boolean,
			default: false,
		},
		compare: {
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
			return Object.keys(this.stats).length <= 0
		},

		/**
		 * Check if we can get stats in anyway.
		 *
		 * @since 3.3.5
		 *
		 * @return {boolean}
		 */
		canGetStats() {
			return this.$moduleVars.can_get_stats > 0
		},

		/**
		 * Check if main menu can be shown.
		 *
		 * @since 3.3.5
		 *
		 * @return {boolean}
		 */
		canShowSettingsMenu() {
			return hasSettingsAccess()
		},
	},

	methods: {},
}
</script>
