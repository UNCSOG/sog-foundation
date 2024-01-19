export default {
	props: {
		stats: Object,
		loading: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			type: null,
			color1: '#0582B5',
			color2: '#17A8E3',
			chartData: {},
		}
	},

	watch: {
		// Whenever stats changes.
		stats() {
			this.setupChartData()
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
			return Object.keys(this.stats).length <= 0 || !this.stats[this.type]
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

		/**
		 * Get the default chart options for mediums.
		 *
		 * @since 3.3.5
		 *
		 * @return {*}
		 */
		chartOptions() {
			return {
				legend: {
					display: false,
				},
				tooltips: {
					xPadding: 15,
					yPadding: 15,
					backgroundColor: 'rgba(51,51,51,0.85)',
					titleSpacing: 0,
					titleMarginBottom: 0,
					bodyFontColor: '#FFFFFF',
					bodyFontSize: 14,
					bodyFontFamily: 'Roboto',
					bodyFontStyle: 'bold',
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
		 * Setup chart data based on the type.
		 *
		 * @since 3.3.5
		 *
		 * @return {void}
		 */
		setupChartData() {
			// No need to set anything if empty.
			if (this.isEmpty) {
				this.chartData = {}

				return
			}

			let vm = this

			let chartData = {
				labels: [],
				datasets: [
					{
						label: '',
						data: [],
						borderWidth: 2,
						borderColor: '#FFFFFF',
						backgroundColor: [],
						hoverBorderWidth: '#FFFFFF',
					},
				],
			}

			let chartLabels = []
			let currentData = []
			let chartColors = []

			// Set labels and data.
			Object.keys(vm.stats[this.type]).forEach(function (key) {
				chartLabels.push(vm.stats[vm.type][key][0])
				currentData.push(vm.stats[vm.type][key][1])

				if (key % 2) {
					chartColors.push(vm.color1)
				} else {
					chartColors.push(vm.color2)
				}
			})

			// Update the data.
			chartData.labels = chartLabels
			chartData.datasets[0].data = currentData
			chartData.datasets[0].backgroundColor = chartColors

			this.chartData = chartData
		},
	},
}
