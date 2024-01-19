<template>
	<div
		role="tabpanel"
		tabindex="0"
		id="beehive-widget-content--audience"
		class="sui-tab-content"
		aria-labelledby="beehive-widget-tab--audience"
		:hidden="'audience' !== activeTab"
		:class="{ active: 'audience' === activeTab }"
	>
		<div class="beehive-tabs">
			<div class="beehive-tabs-menu">
				<div role="tablist" class="beehive-tabs-menu-wrapper">
					<chart-button
						v-for="(item, key) in sections"
						:key="key"
						:title="item.title"
						:name="key"
						:stats="stats"
						:selected-item="selectedTab"
						@tabChange="changeTab"
					/>
				</div>
			</div>
			<div id="beehive-audience-chart" class="beehive-tab-panel">
				<line-chart
					class="beehive-chart"
					role="img"
					aria-hidden="true"
					id="beehive-audience-line-chart"
					:chart-data="chartData"
					:options="getOptions"
				/>

				<users-donut :stats="stats" v-if="!isEmpty" />

				<sui-notice
					v-if="canGetStats && isEmpty"
					type="info"
					class="beehive-margin-top-bottom--30"
				>
					<p>{{ $i18n.notice.empty_data }}</p>
				</sui-notice>

				<sui-notice
					v-else-if="!canGetStats && !isConnected"
					type="error"
				>
					<p v-html="$i18n.notice.auth_required"></p>
				</sui-notice>
			</div>
		</div>
	</div>
</template>

<script>
import moment from 'moment'
import UsersDonut from './audience/users-donut'
import ChartButton from './audience/chart-button'
import SuiNotice from '@/components/sui/sui-notice'
import LineChart from '@/components/charts/line-chart'

export default {
	name: 'Audience',

	props: {
		stats: Object | Array,
		selectedItem: String,
		activeTab: String,
	},

	components: {
		LineChart,
		SuiNotice,
		UsersDonut,
		ChartButton,
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
			chartData: {
				labels: [],
				datasets: [],
			},
			chartOptions: {},
		}
	},

	watch: {
		// When stats change, update chart.
		stats(newStats) {
			this.changeStatsChart()
		},

		// When tab is changed, update the chart.
		selectedTab(tab) {
			this.changeStatsChart()
		},

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
		 * Get the options for the chart.
		 *
		 * @since 3.2.4
		 *
		 * @returns {object}
		 */
		getOptions() {
			if (this.isEmpty) {
				return {
					responsive: true,
					maintainAspectRatio: false,
				}
			} else {
				return this.chartOptions
			}
		},

		/**
		 * Check if we can get the stats.
		 *
		 * @since 3.2.4
		 *
		 * @returns {boolean}
		 */
		canGetStats() {
			return this.$moduleVars.can_get_stats
		},

		/**
		 * Get the default options for the chart.
		 *
		 * @since 3.3.8
		 *
		 * @return {*}
		 */
		defaultChartOptions() {
			return {
				legend: {
					display: false,
				},
				scales: {
					yAxes: [
						{
							gridLines: {
								display: true,
								color: '#E6E6E6',
								zeroLineColor: '#E6E6E6',
								drawBorder: false, // Allow zeroLineColor on xAxes.
							},
							ticks: {
								fontColor: '#676767',
								fontSize: 11,
							},
						},
					],
					xAxes: [
						{
							gridLines: {
								display: true,
								zeroLineColor: 'rgba(0,0,0,0)',
								drawBorder: false, // Allow zeroLineColor on xAxes.
							},
							ticks: {
								fontColor: '#676767',
								fontSize: 11,
							},
						},
					],
				},
				tooltips: {
					xPadding: 15,
					yPadding: 15,
					backgroundColor: 'rgba(51,51,51,0.85)',
					titleFontColor: '#FFFFFF',
					titleFontSize: 14,
					titleFontFamily: 'Roboto',
					titleFontStyle: 'bold',
					titleAlign: 'left',
					titleSpacing: 0,
					titleMarginBottom: 10,
					bodyFontColor: '#FFFFFF',
					bodyFontSize: 14,
					bodyFontFamily: 'Roboto',
					bodyFontStyle: 'normal',
					bodyAlign: 'left',
					cornerRadius: 4,
					displayColors: false,
				},
				responsive: true,
				maintainAspectRatio: false,
			}
		},
	},

	methods: {
		/**
		 * Get the title of the section.
		 *
		 * @since 3.2.4
		 *
		 * @returns {string}
		 */
		getTitle(name) {
			return this.sections[name] ? this.sections[name].title : ''
		},

		/**
		 * Change the selected tab.
		 *
		 * @since 3.2.4
		 *
		 * @returns {void}
		 */
		changeTab(name) {
			this.selectedTab = name
		},

		/**
		 * Update the statistics chart data.
		 *
		 * @since 3.2.4
		 *
		 * @returns {void}
		 */
		changeStatsChart() {
			if (this.isEmpty) {
				this.chartData = {
					labels: [],
					datasets: [],
				}

				return
			}

			if (
				!this.sections[this.selectedTab] ||
				!this.stats[this.selectedTab]
			) {
				return
			}

			let labels = []
			let data = []
			let chartLinesX = []

			let chartOptions = this.defaultChartOptions

			// Get the title.
			let title = this.getTitle(this.selectedTab)

			// Get the colors.
			let color = this.sections[this.selectedTab].color

			let chartData = {
				labels: [],
				datasets: [
					{
						label: title,
						data: [],
						borderWidth: 2,
						borderColor: color,
						backgroundColor: 'rgba(0,0,0,0)',
						pointRadius: 4,
						pointBorderColor: color,
						pointBackgroundColor: '#FFFFFF',
						pointHoverBackgroundColor: color,
					},
				],
			}

			// Get stats.
			let stats = this.stats[this.selectedTab]

			// Tooltip callbacks.
			chartOptions.tooltips.callbacks = {}

			if (this.selectedTab === 'average_sessions') {
				chartOptions.tooltips.callbacks.label = function (tooltipItem) {
					// Format to 00:00:00.
					let time = moment
						.utc(tooltipItem.value * 1000)
						.format('HH:mm:ss')

					return title + ' : ' + time
				}
			} else if (this.selectedTab === 'bounce_rates') {
				chartOptions.tooltips.callbacks.label = function (tooltipItem) {
					return title + ' : ' + tooltipItem.value + '%'
				}
			}

			// Setup data set for the current period.
			Object.keys(stats).forEach(function (key, idx, array) {
				labels.push(stats[key][0])
				data.push(stats[key][1])

				if (idx === array.length - 1) {
					chartLinesX.push('rgba(0,0,0,0)')
				} else {
					chartLinesX.push('#E6E6E6')
				}
			})

			chartData.labels = labels
			chartData.datasets[0].data = data

			this.chartData = chartData
			this.chartOptions = chartOptions
		},
	},
}
</script>
