<template>
	<div id="beehive-widget-header">
		<div class="beehive-left-actions">
			<a :href="$vars.urls.statistics" v-if="canShowStatisticsMenu">
				<i class="sui-icon-graph-bar" aria-hidden="true"></i>
				{{ $i18n.label.all_stats }}
			</a>
			<span
				class="beehive-separator"
				aria-hidden="true"
				v-if="canShowStatisticsMenu"
			></span>
			<button @click.prevent="refreshStats">
				<i class="sui-icon-refresh" aria-hidden="true"></i>
				{{ $i18n.label.refresh_data }}
			</button>
		</div>

		<div class="beehive-right-actions">
			<sui-calendar-range
				id="beehive-stats-datepicker"
				:periods="$vars.dates.periods"
				:start-date="$vars.dates.start_date"
				:end-date="$vars.dates.end_date"
				:selected-label="$vars.dates.selected_label"
				@periodChange="periodChange"
			/>
		</div>
	</div>
</template>

<script>
import { hasStatisticsAccess } from '@/helpers/utils'
import SuiCalendarRange from '@/components/sui/sui-calendar-range'

export default {
	name: 'WidgetHeader',

	components: { SuiCalendarRange },

	computed: {
		/**
		 * Check if statistics menu can be shown.
		 *
		 * @since 3.3.5
		 *
		 * @return {boolean}
		 */
		canShowStatisticsMenu() {
			return hasStatisticsAccess()
		},
	},

	methods: {
		/**
		 * Refresh the current period stats.
		 *
		 * @since 3.3.0
		 */
		refreshStats() {
			this.$emit('refreshStats')
		},

		/**
		 * Change the period of the stats.
		 *
		 * @since 3.3.0
		 *
		 * @param {object} data Period data.
		 */
		periodChange(data) {
			// Emit new event on date period change.
			this.$emit('periodChange', data)
		},
	},
}
</script>
