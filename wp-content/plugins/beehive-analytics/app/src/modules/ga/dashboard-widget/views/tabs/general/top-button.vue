<template>
	<div class="beehive-button-holder">
		<button
			class="beehive-button"
			@click="openTab"
			v-if="(!canGetStats && !isConnected) || isEmpty || '' === getValue"
		>
			<span class="beehive-button-name">
				{{ title }}
			</span>
			<span class="beehive-button-value">-</span>
			<i
				class="beehive-button-icon sui-icon-chevron-right sui-lg"
				aria-hidden="true"
			></i>
		</button>

		<button class="beehive-button" @click="openTab" v-else>
			<span class="beehive-button-name">
				{{ title }}
			</span>
			<span class="beehive-button-stats">
				<span class="beehive-button-value beehive-blue beehive-lg">
					{{ getValue }}
				</span>
				<span
					class="beehive-button-trend"
					:class="getTrendClass"
					v-html="getTrendHtml"
				></span>
			</span>
			<i
				class="beehive-button-icon sui-icon-chevron-right sui-lg"
				aria-hidden="true"
			></i>
		</button>
	</div>
</template>

<script>
export default {
	name: 'TopButton',

	props: {
		stats: Object,
		tab: String,
		title: String,
		name: String,
	},

	computed: {
		/**
		 * Check if current site can get statistics.
		 *
		 * @since 3.3.8
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

		/**
		 * Get classes for the trend element.
		 *
		 * @since 3.3.8
		 *
		 * @return {*}
		 */
		getTrendClass() {
			return {
				'beehive-red': this.getTrendValue < 0,
				'beehive-green': this.getTrendValue >= 0,
			}
		},

		/**
		 * Get the value from the stats data.
		 *
		 * @since 3.3.8
		 *
		 * @return {string}
		 */
		getValue() {
			let value = ''

			if (
				this.stats.summary &&
				this.stats.summary[this.name] &&
				this.stats.summary[this.name].value
			) {
				value = this.stats.summary[this.name].value
			}

			return value
		},

		/**
		 * Get the trend value from the stats data.
		 *
		 * @since 3.3.8
		 *
		 * @return {number}
		 */
		getTrendValue() {
			let value = 0

			if (
				this.stats.summary &&
				this.stats.summary[this.name] &&
				this.stats.summary[this.name].trend
			) {
				value = this.stats.summary[this.name].trend
			}

			return value
		},

		/**
		 * Get the HTML output for the trend element.
		 *
		 * @since 3.3.8
		 *
		 * @return {string}
		 */
		getTrendHtml() {
			let value = this.getTrendValue
			let actual = Math.abs(value)

			if (value < 0) {
				return (
					actual +
					'% <i class="sui-icon-arrow-down sui-sm" aria-hidden="true"></i>'
				)
			} else if (value > 0) {
				return (
					'<i class="sui-icon-arrow-up sui-sm" aria-hidden="true"></i>' +
					actual +
					'%'
				)
			} else {
				return '0%'
			}
		},
	},

	methods: {
		/**
		 * Open the selected tab.
		 *
		 * @since 3.3.8
		 */
		openTab() {
			// Emit tab change.
			this.$emit('tabChange', this.name)

			// Get tab unique ID to open.
			let tab = jQuery('#beehive-widget-tab--' + this.tab)

			// Simulate click on tab to open it.
			tab.click()
		},
	},
}
</script>
