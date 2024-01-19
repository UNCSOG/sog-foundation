<template>
	<div class="beehive-tabs">
		<div class="beehive-tabs-tablist">
			<tab-button
				v-for="(item, name) in getSummary"
				:key="name"
				:name="name"
				:item="item"
				:sections="getSections"
				:selected="selected"
				@changeTab="changeTab"
			/>
		</div>
		<div id="beehive-visitors-chart" class="beehive-tab-panel">
			<line-chart
				class="beehive-chart"
				role="img"
				aria-hidden="true"
				:id="chartId"
				:chart-data="chartData"
				:options="getOptions"
			/>

			<!-- Legends display -->
			<chart-legends
				v-if="compare && canGetStats"
				:selected="selected"
				:sections="getSections"
				:periods="periods"
			/>

			<p class="sui-screen-reader-text" v-if="isEmpty">
				{{ $i18n.desc.empty_visitors_chart }}
			</p>
		</div>
	</div>
</template>

<script>
import moment from 'moment'
import TabButton from './tab-button'
import ChartLegends from './chart-legends'
import LineChart from '@/components/charts/line-chart'

export default {
	name: 'VisitorsChart',

	components: {
		LineChart,
		TabButton,
		ChartLegends,
	},

	props: {
		stats: Object,
		periods: Object,
		compare: Boolean,
		loading: Boolean,
	},

	data() {
		return {
			selected: 'sessions',
			chartId: 'beehive-visitors-line-chart',
			chartOptions: {},
			chartData: {
				labels: [],
				datasets: [],
			},
		}
	},

	watch: {
		// When stats change, update chart.
		stats() {
			this.changeStatsChart()
		},

		// When tab is changed, update the chart.
		selected() {
			this.changeStatsChart()
		},

		// When comparison checkbox is checked.
		compare() {
			this.changeComparison()
		},
	},

	computed: {
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
		 * Check if selected stats item is empty.
		 *
		 * @since 3.3.8
		 *
		 * @return {boolean}
		 */
		isSelectedEmpty() {
			return (
				this.isEmpty ||
				!this.stats[this.selected] ||
				this.stats[this.selected].length <= 0
			)
		},

		/**
		 * Check if we can get stats in anyway.
		 *
		 * @since 3.3.7
		 *
		 * @return {boolean}
		 */
		canGetStats() {
			return this.$moduleVars.can_get_stats > 0
		},

		/**
		 * Get summary data for the chart.
		 *
		 * @since 3.2.5
		 *
		 * @returns {object}
		 */
		getSummary() {
			if (this.stats.summary) {
				let items = {}

				Object.keys(this.stats.summary).forEach((name) => {
					if (this.canShow(name)) {
						items[name] = this.stats.summary[name]
					}
				})

				return items
			} else {
				return {}
			}
		},

		/**
		 * Get chart sections color and label data.
		 *
		 * @since 3.3.5
		 *
		 * @return {*}
		 */
		getSections() {
			return {
				sessions: {
					color: ['#17A8E3', '#ADDCF2'],
					title: this.$i18n.label.sessions,
				},
				users: {
					color: ['#2D8CE2', '#9DD0FF'],
					title: this.$i18n.label.users,
				},
				pageviews: {
					color: ['#8D00B1', '#E9CCF0'],
					title: this.$i18n.label.pageviews,
				},
				page_sessions: {
					color: ['#3DB8C2', '#C0EBEF'],
					title: this.$i18n.label.page_sessions,
				},
				average_sessions: {
					color: ['#2B7BA1', '#C0EBEF'],
					title: this.$i18n.label.average_sessions,
				},
				bounce_rates: {
					color: ['#FFB17C', '#FFE3CF'],
					title: this.$i18n.label.bounce_rates,
				},
			}
		},

		/**
		 * Get the chart options object.
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
		 * Get default chart options data.
		 *
		 * These default config is used to render
		 * the chart every time.
		 *
		 * @since 3.3.5
		 *
		 * @return {*}
		 */
		getDefaultChartOptions() {
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
				tooltips: {},
				responsive: true,
				maintainAspectRatio: false,
			}
		},

		/**
		 * Get the previous period data with options.
		 *
		 * @since 3.2.4
		 *
		 * @returns {object}
		 */
		getPrevPeriodDataSet() {
			let data = []

			// Previous period data.
			let stats = this.stats[this.selected].previous

			// Get the title.
			let title = this.getTitle(this.selected)

			// Color codes.
			let colors = this.getSections[this.selected].color

			// Setup data set for the previous period.
			Object.keys(stats).forEach(function (key) {
				data.push(stats[key][1])
			})

			return {
				label: title,
				data: data,
				borderWidth: 2,
				borderColor: colors[1],
				backgroundColor: 'rgba(0,0,0,0)',
				pointRadius: 4,
				pointBorderColor: colors[1],
				pointBackgroundColor: '#FFFFFF',
				pointHoverBackgroundColor: colors[1],
			}
		},

		/**
		 * Get the previous period data with options.
		 *
		 * @since 3.2.4
		 *
		 * @returns {object}
		 */
		getPrevPeriodLabelSet() {
			let labels = []

			// Previous period data.
			let stats = this.stats[this.selected].previous

			// Setup data set for the previous period.
			Object.keys(stats).forEach(function (key) {
				labels.push(stats[key][0])
			})

			return labels
		},

		/**
		 * Get default tooltip options for the chart.
		 *
		 * @since 3.3.5
		 *
		 * @return {*}
		 */
		defaultTooltipOptions() {
			return {
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
				bodySpacing: 10,
				bodyAlign: 'left',
				cornerRadius: 4,
				displayColors: false,
				mode: 'index',
				intersect: false,
			}
		},

		/**
		 * Get the html tooltip configuration.
		 *
		 * When comparison chart is enabled, we need
		 * to use HTML tooltip.
		 *
		 * @since 3.2.4
		 *
		 * @return {*}
		 */
		htmlTooltipOptions() {
			let options = this.defaultTooltipOptions

			options.enabled = false
			options.position = 'nearest'
			options.intersect = true

			options.custom = function (tooltip) {
				// Tooltip Element
				let tooltipEl = document.getElementById('chartjs-tooltip')

				if (!tooltipEl) {
					tooltipEl = document.createElement('div')
					tooltipEl.id = 'chartjs-tooltip'
					tooltipEl.innerHTML = '<table></table>'
					this._chart.canvas.parentNode.appendChild(tooltipEl)
				}

				// Hide if no tooltip
				if (tooltip.opacity === 0) {
					tooltipEl.style.opacity = 0
					return
				}

				// Set caret Position
				tooltipEl.classList.remove('above', 'below', 'no-transform')
				if (tooltip.yAlign) {
					tooltipEl.classList.add(tooltip.yAlign)
				} else {
					tooltipEl.classList.add('no-transform')
				}

				function getBody(bodyItem) {
					return bodyItem.lines
				}

				// Set Text
				if (tooltip.body) {
					let titleLines = tooltip.title || []
					let bodyLines = tooltip.body.map(getBody)

					let innerHtml = '<thead>'

					titleLines.forEach(function (title) {
						innerHtml += '<tr><th>' + title + '</th></tr>'
					})
					innerHtml += '</thead><tbody>'

					bodyLines.forEach(function (body, i) {
						let colors = tooltip.labelColors[i]
						let style = 'background:' + colors.borderColor
						style += '; border-color:' + colors.borderColor
						style += '; border-width: 2px'
						let span =
							'<span class="chartjs-tooltip-key" style="' +
							style +
							'"></span>'
						innerHtml += '<tr><td>' + span + body + '</td></tr>'
					})
					innerHtml += '</tbody>'

					let tableRoot = tooltipEl.querySelector('table')
					tableRoot.innerHTML = innerHtml
				}

				let positionY = this._chart.canvas.offsetTop
				let positionX = this._chart.canvas.offsetLeft

				// Display, position, and set styles for font
				tooltipEl.style.opacity = 1
				tooltipEl.style.left = positionX + tooltip.caretX + 'px'
				tooltipEl.style.top = positionY + tooltip.caretY + 'px'
				tooltipEl.style.fontFamily = tooltip._bodyFontFamily
				tooltipEl.style.fontSize = tooltip.bodyFontSize + 'px'
				tooltipEl.style.fontStyle = tooltip._bodyFontStyle
				tooltipEl.style.padding =
					tooltip.yPadding + 'px ' + tooltip.xPadding + 'px'
			}

			return options
		},
	},

	methods: {
		/**
		 * Check if the current item can be shown.
		 *
		 * @since 3.2.4
		 *
		 * @returns {boolean}
		 */
		canShow(name) {
			return this.getSections.hasOwnProperty(name)
		},

		/**
		 * Check if the current item can be shown.
		 *
		 * @since 3.2.4
		 *
		 * @returns {boolean}
		 */
		changeTab(tab) {
			this.selected = tab
		},

		/**
		 * Get the title of the item.
		 *
		 * @since 3.2.4
		 *
		 * @returns {string}
		 */
		getTitle(name) {
			return this.getSections[name].title
		},

		/**
		 * Update the chart when stats are changed.
		 *
		 * @since 3.2.4
		 */
		changeStatsChart() {
			// When data is empty.
			if (this.isSelectedEmpty) {
				this.chartData = {
					labels: [],
					datasets: [],
				}
				this.chartOptions = this.getDefaultChartOptions
				this.chartOptions.tooltips = this.defaultTooltipOptions

				return
			}

			let vm = this

			let chartLabels = []
			let currentData = []
			let chartLinesX = []

			let chartOptions = this.getDefaultChartOptions

			// Get the colors.
			let colors = this.getSections[this.selected].color

			// Get the title.
			let title = this.getTitle(this.selected)

			let chartData = {
				labels: [],
				datasets: [
					{
						label: title,
						data: [],
						borderWidth: 2,
						borderColor: colors[0],
						backgroundColor: 'rgba(0,0,0,0)',
						pointRadius: 4,
						pointBorderColor: colors[0],
						pointBackgroundColor: '#FFFFFF',
						pointHoverBackgroundColor: colors[0],
					},
				],
			}

			// Get stats item.
			const stats = this.stats[this.selected].current

			// Setup data set for the current period.
			Object.keys(stats).forEach(function (key, idx, array) {
				chartLabels.push(stats[key][0])
				currentData.push(stats[key][1])

				if (idx === array.length - 1) {
					chartLinesX.push('rgba(0,0,0,0)')
				} else {
					chartLinesX.push('#E6E6E6')
				}
			})

			// Set each grid lines color.
			chartOptions.scales.xAxes[0].gridLines['color'] = chartLinesX

			if (this.compare) {
				chartOptions.tooltips = this.htmlTooltipOptions
			} else {
				chartOptions.tooltips = this.defaultTooltipOptions
			}

			// Tooltip callbacks.
			chartOptions.tooltips.callbacks = {}

			chartOptions.tooltips.callbacks.label = (tooltipItem) => {
				let value = tooltipItem.value
				let index = tooltipItem.index
				if (vm.selected === 'average_sessions') {
					value = moment.utc(value * 1000).format('HH:mm:ss')
				} else if (vm.selected === 'bounce_rates') {
					value = value + '%'
				}

				if (!this.compare) {
					return title + ' : ' + value // Single tooltip.
				} else if (tooltipItem.datasetIndex === 1) {
					return this.getPrevPeriodLabelSet[index] + ' : ' + value // Previous period tooltip label.
				} else {
					return tooltipItem.label + ' : ' + value // Current period label.
				}
			}

			// Set title callback.
			chartOptions.tooltips.callbacks.title = (tooltipItem) => {
				if (this.compare) {
					return title
				} else {
					return tooltipItem[0].label
				}
			}

			// Set label.
			chartData.datasets[0].label = title

			// Update the data.
			chartData.labels = chartLabels
			chartData.datasets[0].data = currentData

			if (0 === currentData.length) {
				// Replace chart colors when data is empty.
				chartData.datasets[0].borderColor = 'rgba(0, 0, 0, 0)'
				chartData.datasets[0].pointRadius = 0
				chartData.datasets[0].pointBorderColor = 'rgba(0, 0, 0, 0)'
				chartData.datasets[0].pointBackgroundColor = 'rgba(0, 0, 0, 0)'
				chartData.datasets[0].pointHoverBackgroundColor =
					'rgba(0, 0, 0, 0)'

				// Hide tooltip when data is empty.
				chartOptions.tooltips['enabled'] = false

				// Replace some chart options to show a clean chart.
				chartOptions.scales.yAxes[0].ticks['suggestedMin'] = 40000
				chartOptions.scales.yAxes[0].ticks['suggestedMax'] = 44000
				chartOptions.scales.yAxes[0].ticks['beginAtZero'] = true
				chartOptions.scales.yAxes[0].ticks['maxTicksLimit'] = 5
			}

			// If comparison checkbox is checked.
			if (this.compare) {
				chartData.datasets.push(this.getPrevPeriodDataSet)
			}

			this.chartData = chartData

			this.chartOptions = chartOptions
		},

		/**
		 * Change the chart comparison option.
		 *
		 * Make changes to chart when comparison checkbox is checked.
		 *
		 * @since 3.2.4
		 */
		changeComparison() {
			if (this.isEmpty) {
				return
			}

			// Update the chart.
			this.changeStatsChart()
		},
	},
}
</script>
