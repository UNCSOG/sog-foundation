<template>
	<sui-box
		title-icon="magnifying-glass-search"
		aria-live="polite"
		:title="$i18n.label.search_engines"
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
				<pie-chart
					class="beehive-chart-pie"
					:chart-data="chartData"
					:options="chartOptions"
				/>
				<ul class="beehive-chart-pie-legend">
					<doughnut-legend
						v-for="(item, name) in stats.search_engines"
						:key="name"
						:title="item[0]"
						:value="item[1]"
					/>
				</ul>
			</fragment>
		</template>
	</sui-box>
</template>

<script>
import Pie from './mixins/pie'
import SuiBox from '@/components/sui/sui-box'
import PieChart from '@/components/charts/pie-chart'
import DoughnutLegend from './components/doughnut-legend'

export default {
	name: 'SearchEngines',

	components: {
		SuiBox,
		PieChart,
		DoughnutLegend,
	},

	mixins: [Pie],

	data() {
		return {
			type: 'search_engines',
		}
	},
}
</script>
