<template>
	<button
		role="tab"
		class="beehive-tab"
		aria-controls="beehive-audience-chart"
		:class="buttonClass"
		:aria-selected="ariaSelected"
		:tabindex="tabIndex"
		@click="changeTab"
	>
		<span class="beehive-item-wrapper">
			<span class="beehive-item-title">
				{{ title }}
			</span>
			<span class="beehive-item-value" v-if="!isConnected || isEmpty">
				0
			</span>
			<span class="beehive-item-stats" v-else>
				<span class="beehive-item-value">
					{{ getValue }}
				</span>

				<span
					class="beehive-item-trend beehive-red"
					v-if="getTrendValue > 0 && 'bounce_rates' === name"
				>
					<i class="sui-icon-arrow-up sui-sm" aria-hidden="true"></i>
					{{ Math.abs(getTrendValue) }}%
				</span>
				<span
					class="beehive-item-trend beehive-green"
					v-else-if="getTrendValue > 0"
				>
					<i class="sui-icon-arrow-up sui-sm" aria-hidden="true"></i>
					{{ Math.abs(getTrendValue) }}%
				</span>
				<span
					class="beehive-item-trend beehive-green"
					v-else-if="getTrendValue < 0 && 'bounce_rates' === name"
				>
					<i
						class="sui-icon-arrow-down sui-sm"
						aria-hidden="true"
					></i>
					{{ Math.abs(getTrendValue) }}%
				</span>
				<span
					class="beehive-item-trend beehive-red"
					v-else-if="getTrendValue < 0"
				>
					<i
						class="sui-icon-arrow-down sui-sm"
						aria-hidden="true"
					></i>
					{{ Math.abs(getTrendValue) }}%
				</span>
				<span class="beehive-item-trend" v-else>
					0%
				</span>
			</span>
		</span>
	</button>
</template>

<script>
export default {
	name: 'ChartButton',

	props: {
		stats: Object | Array,
		name: String,
		title: String,
		selectedItem: String,
	},

	data() {
		return {
			selectedTab: this.selectedItem, // Default item.

			sections: {
				sessions: {
					color: '#17A8E3',
					title: this.$i18n.label.sessions,
				},
				users: {
					color: '#2D8CE2',
					title: this.$i18n.label.users,
				},
				pageviews: {
					color: '#8D00B1',
					title: this.$i18n.label.pageviews,
				},
				page_sessions: {
					color: '#3DB8C2',
					title: this.$i18n.label.page_sessions,
				},
				average_sessions: {
					color: '#2B7BA1',
					title: this.$i18n.label.average_sessions,
				},
				bounce_rates: {
					color: '#FFB17C',
					title: this.$i18n.label.bounce_rates,
				},
			},
		}
	},

	watch: {
		selectedItem(tab) {
			this.selectedTab = tab
		},
	},

	computed: {
		/**
		 * Check if stats are empty.
		 *
		 * @since 3.2.4
		 *
		 * @returns {boolean}
		 */
		isEmpty() {
			return Object.keys(this.stats).length <= 0
		},

		/**
		 * Check if user has logged in to Google.
		 *
		 * @since 3.2.4
		 *
		 * @returns {boolean}
		 */
		isConnected() {
			return this.$store.state.helpers.google.logged_in
		},

		/**
		 * Get tab button active class.
		 *
		 * @since 3.2.4
		 *
		 * @returns {object}
		 */
		buttonClass() {
			return {
				'beehive-active': this.selectedTab === this.name,
				'beehive-empty-tab': this.isEmpty,
			}
		},

		/**
		 * Get aria-selected attribute value.
		 *
		 * @since 3.2.4
		 *
		 * @returns {string}
		 */
		ariaSelected() {
			// Should be string.
			return this.name === this.selectedTab ? 'true' : 'false'
		},

		/**
		 * Get the tabindex value.
		 *
		 * @since 3.2.4
		 *
		 * @returns {int|boolean}
		 */
		tabIndex() {
			// Should be string.
			return this.name === this.selectedTab ? -1 : false
		},

		/**
		 * Get the value of current section.
		 *
		 * @since 3.2.4
		 *
		 * @returns {*}
		 */
		getValue() {
			let value = 0

			if (
				this.stats.summary &&
				this.stats.summary[this.name] &&
				this.stats.summary[this.name].value
			) {
				value = this.stats.summary[this.name].value
			}

			// Bounce rates should have %.
			if ('bounce_rates' === this.name) {
				value = value + '%'
			}

			return value
		},

		/**
		 * Get the trend value of the item.
		 *
		 * @since 3.2.4
		 *
		 * @returns {int}
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
	},

	methods: {
		/**
		 * Change the selected tab.
		 *
		 * @since 3.2.4
		 *
		 * @returns {void}
		 */
		changeTab() {
			this.selectedTab = this.name

			// Emit tab change.
			this.$emit('tabChange', this.name)
		},
	},
}
</script>
