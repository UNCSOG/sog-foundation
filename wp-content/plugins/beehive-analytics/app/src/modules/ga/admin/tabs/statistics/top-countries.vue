<template>
	<sui-box
		title-icon="web-globe-world"
		aria-live="polite"
		:title="$i18n.label.top_countries"
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

			<fragment v-else>
				<GChart
					type="GeoChart"
					:settings="{ packages: ['geochart'] }"
					:data="chartData"
					:options="getChartOptions"
					@ready="onChartReady"
				/>

				<!-- Get the list table -->
				<list-table :stats="stats" />
			</fragment>
		</template>
	</sui-box>
</template>

<script>
import { GChart } from 'vue-google-charts'
import SuiBox from '@/components/sui/sui-box'
import ListTable from './countries/list-table'

export default {
	name: 'TopCountries',

	components: {
		SuiBox,
		GChart,
		ListTable,
	},

	props: {
		stats: Object,
		loading: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			chartData: {},
			chart: null,
			chartApi: null,
		}
	},

	watch: {
		stats() {
			// Setup the list.
			this.setupList()
		},
	},

	computed: {
		/**
		 * Check if Google account is connected.
		 *
		 * @since 3.2.0
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
			return Object.keys(this.stats).length <= 0 || !this.stats.countries
		},

		/**
		 * Check if we can show stats.
		 *
		 * @since 3.3.8
		 *
		 * @return {*}
		 */
		canGetStats() {
			return this.$moduleVars.can_get_stats <= 0
		},

		/**
		 * Get the default chart options.
		 *
		 * @since 3.3.5
		 *
		 * @return {*}
		 */
		getChartOptions() {
			return {
				chart: {
					title: this.$i18n.label.country_sessions,
				},
				colorAxis: {
					colors: ['#6DD5FF', '#49BFEF', '#17A8E3', '#0582B5'],
				},
				backgroundColor: {
					fill: '#FFFFFF',
					strokeWidth: 0,
				},
				datalessRegionColor: '#DDDDDD',
				tooltip: {
					isHtml: true,
					showTitle: false,
					ignoreBounds: true,
					textStyle: {
						color: '#FFFFFF',
						fontName: 'Roboto',
						fontSize: 13,
					},
				},
			}
		},
	},

	methods: {
		/**
		 * Setup chart data for the geo graph.
		 *
		 * To render Google geo chart, we need to setup
		 * the data table.
		 *
		 * @since 3.3.5
		 *
		 * @return {void}
		 */
		setupList() {
			// Data and map api should be available.
			if (this.isEmpty || !this.chartApi) {
				this.chartData = {}

				return
			}

			let vm = this

			let chartData = []

			const dataTable = new this.chartApi.visualization.DataTable()

			// Chart items.
			dataTable.addColumn('string', this.$i18n.label.country)
			dataTable.addColumn('number', this.$i18n.label.sessions)
			dataTable.addColumn({
				type: 'string',
				role: 'tooltip',
				p: { html: true },
			})

			// Chart data.
			Object.keys(vm.stats.countries).forEach(function (key) {
				chartData.push([
					vm.stats.countries[key][0],
					vm.stats.countries[key][2],
					vm.geoToolTip(
						vm.stats.countries[key][0],
						vm.stats.countries[key][1],
						vm.stats.countries[key][2]
					), // Custom tooltip.
				])
			})

			dataTable.addRows(chartData)

			this.chartData = dataTable
		},

		/**
		 * Create custom tooltip html.
		 *
		 * Custom html tooltip should be styled within
		 * our custom style.
		 *
		 * @param {string} countryName
		 * @param {string} countryCode
		 * @param {integer} sessions
		 *
		 * @since 3.2.0
		 *
		 * @returns {string}
		 */
		geoToolTip(countryName, countryCode, sessions) {
			return (
				'<span class="beehive-charts-geotooltip">' +
				'<span class="beehive-flag beehive-flag-unframed beehive-flag-' +
				countryCode +
				'" aria-hidden="true"></span>' +
				'<span class="beehive-country-sessions">' +
				'<span class="beehive-country-name">' +
				countryName +
				'</span>' +
				'<span class="sui-screen-reader-text">' +
				this.$i18n.label.has +
				'</span> ' +
				'<strong>' +
				sessions +
				'</strong> ' +
				this.$i18n.label.sessions +
				'</span>' +
				'</span>'
			)
		},

		/**
		 * On chart ready, setup chart API object.
		 *
		 * @param chart
		 * @param api
		 *
		 * @since 3.2.4
		 */
		onChartReady(chart, api) {
			this.chartApi = api

			if (this.isEmpty) {
				return
			}

			// Setup list if ready.
			this.setupList()
		},
	},
}
</script>
