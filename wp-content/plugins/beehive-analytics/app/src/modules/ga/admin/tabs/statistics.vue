<template>
	<div>
		<!-- Visitors chart -->
		<visitors
			:loading="loading"
			:stats="stats"
			:compare="compare"
			:periods="periods"
			v-if="canView('visitors')"
		/>

		<!-- Realtime visits -->
		<realtime-visits :loading="loading" :stats="stats" />

		<div class="sui-row">
			<div class="sui-col-md-4">
				<!-- Mediums graph -->
				<mediums
					:loading="loading"
					:stats="stats"
					v-if="canView('mediums')"
				/>
			</div>
			<div class="sui-col-md-4">
				<!-- Social Networks graph -->
				<social-networks
					:loading="loading"
					:stats="stats"
					v-if="canView('social_networks')"
				/>
			</div>
			<div class="sui-col-md-4" v-if="canView('search_engines')">
				<!-- Search Engines graph -->
				<search-engines :loading="loading" :stats="stats" />
			</div>
		</div>

		<div class="sui-row">
			<div class="sui-col" v-if="canView('countries')">
				<!-- Top Countries graph -->
				<top-countries :loading="loading" :stats="stats" />
			</div>
			<div class="sui-col" v-if="canView('pages')">
				<!-- Top Pages graph -->
				<top-pages :loading="loading" :stats="stats" />
			</div>
		</div>
	</div>
</template>

<script>
import { restGetStats } from '@/helpers/api'
import Mediums from './statistics/mediums'
import Visitors from './statistics/visitors'
import TopPages from './statistics/top-pages'
import { canViewStats } from '@/helpers/utils'
import TopCountries from './statistics/top-countries'
import SearchEngines from './statistics/search-engines'
import RealtimeVisits from './statistics/realtime-visits'
import SocialNetworks from './statistics/social-networks'

export default {
	name: 'Statistics',

	components: {
		Mediums,
		Visitors,
		TopPages,
		TopCountries,
		SearchEngines,
		SocialNetworks,
		RealtimeVisits,
	},

	props: {
		compare: {
			type: Boolean,
			default: false,
		},
		loading: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			stats: {},
			startDate: this.$vars.dates.start_date,
			endDate: this.$vars.dates.end_date,
			selectedDate: this.$vars.dates.selected_label,
			periods: {
				current: {
					from: '',
					to: '',
				},
				previous: {
					from: '',
					to: '',
				},
			},
		}
	},

	mounted() {
		// Load stats.
		this.getStats()

		// After the stats refresh, get again.
		this.$root.$on('statsRefreshCompleted', () => {
			this.getStats()
		})

		// After the stats refresh, get again.
		this.$root.$on('statsPeriodChanged', (data) => {
			this.periodChange(data)
		})

		// After the stats type change, get again.
		this.$root.$on('statsTypeChanged', (force) => {
			if ( this.isStatsPage || force ) {
				this.getStats()
			}
		})
	},

	computed: {
		/**
		 * Check if we can get the stats data.
		 *
		 * @since 3.2.4
		 *
		 * @return {boolean}
		 */
		canGetStats() {
			return this.$moduleVars.can_get_stats > 0
		},

		/**
		 * Check if current page is statistics page.
		 *
		 * @since 3.4.0
		 *
		 * @return {boolean}
		 */
		isStatsPage() {
			return this.$route && '/statistics' === this.$route.path
		},
	},

	methods: {
		/**
		 * Check if we can view the stats.
		 *
		 * @param {string} type Stats type.
		 *
		 * @since 3.2.4
		 *
		 * @return {string|boolean}
		 */
		canView(type) {
			return canViewStats(type, 'statistics')
		},

		/**
		 * Get the stats using the API.
		 *
		 * Setup the period comparison also.
		 *
		 * @since 3.2.4
		 *
		 * @returns {void}
		 */
		async getStats() {
			if (!this.canGetStats) {
				return
			}

			this.$emit('update:loading', true)

			await restGetStats({
				path: 'stats/statistics',
				params: {
					from: this.startDate,
					to: this.endDate,
					network: this.isNetwork() ? 1 : 0,
				},
			}).then((response) => {
				if (response.success && response.data && response.data.stats) {
					if (Object.keys(response.data.stats).length > 0) {
						this.stats = response.data.stats
					} else {
						this.stats = {} // Empty data.
					}

					if (response.data.periods) {
						const periods = response.data.periods
						this.periods = {
							current: {
								from: periods.current.from,
								to: periods.current.to,
							},
							previous: {
								from: periods.previous.from,
								to: periods.previous.to,
							},
						}
					} else {
						this.periods = {
							current: {
								from: this.startDate,
								to: this.endDate,
							},
							previous: {
								from: this.startDate,
								to: this.endDate,
							},
						}
					}
				} else {
					this.stats = {} // Error.
				}

				this.$emit('update:loading', false)
			})
		},

		/**
		 * Process the period change action.
		 *
		 * On period change, update the dates and then
		 * reload the stats.
		 *
		 * @since 3.2.4
		 *
		 * @returns {void}
		 */
		async periodChange(data) {
			// Set from and to dates.
			this.startDate = data.startDate
			this.endDate = data.endDate
			this.selectedDate = data.selected

			// Make the API request.
			await this.getStats()
		},
	},
}
</script>
