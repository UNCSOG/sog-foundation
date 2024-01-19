<template>
	<div tabindex="-1" class="beehive-options-sidenote" aria-hidden="true">
		<span class="beehive-sidenote-left">
			<i
				class="beehive-sidenote-indicator"
				:style="getCurrentLegendStyle"
				aria-hidden="true"
			></i>
			{{ getCurrentPeriodText }}
		</span>
		<span class="beehive-sidenote-right">
			<i
				class="beehive-sidenote-indicator"
				:style="getPreviousLegendStyle"
				aria-hidden="true"
			></i>
			{{ getPreviousPeriodText }}
		</span>
	</div>
</template>

<script>
import moment from 'moment'

export default {
	name: 'ChartLegends',

	props: {
		periods: Object,
		sections: Object,
		selected: String,
	},

	computed: {
		/**
		 * Get the current period legend text.
		 *
		 * @since 3.2.7
		 *
		 * @returns {string}
		 */
		getCurrentPeriodText() {
			return this.getPeriodLegend(
				this.periods.current.from,
				this.periods.current.to
			)
		},

		/**
		 * Get the previous period legend text.
		 *
		 * @since 3.2.7
		 *
		 * @returns {string}
		 */
		getPreviousPeriodText() {
			return this.getPeriodLegend(
				this.periods.previous.from,
				this.periods.previous.to
			)
		},

		/**
		 * Get the current period legend style.
		 *
		 * @since 3.3.5
		 *
		 * @returns {object}
		 */
		getCurrentLegendStyle() {
			return {
				'background-color': this.sections[this.selected].color[0],
			}
		},

		/**
		 * Get the previous period legend style.
		 *
		 * @since 3.3.5
		 *
		 * @returns {object}
		 */
		getPreviousLegendStyle() {
			return {
				'background-color': this.sections[this.selected].color[1],
			}
		},
	},

	methods: {
		/**
		 * Get the legend period text.
		 *
		 * @since 3.2.7
		 *
		 * @returns {string}
		 */
		getPeriodLegend(from, to) {
			if (from === to) {
				let start = moment(from)
				return start.format('MMM D, YYYY')
			} else {
				let start = moment(from)
				let end = moment(to)

				return (
					start.format('MMM D, YYYY') +
					' - ' +
					end.format('MMM D, YYYY')
				)
			}
		},
	},
}
</script>
