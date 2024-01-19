<template>
	<sui-box
		title-icon="community-people"
		aria-live="polite"
		:title="$i18n.label.mediums"
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
					<li
						class="beehive-legend-item"
						v-for="(item, name) in stats.mediums"
						:key="name"
					>
						<span
							class="beehive-legend-item-color"
							aria-hidden="true"
						></span>
						<span class="beehive-legend-item-name">
							<span>{{ item[0] }}</span>
							<strong>{{ item[1] }}</strong>
						</span>
					</li>
				</ul>
			</fragment>
		</template>
	</sui-box>
</template>

<script>
import Pie from './mixins/pie'
import SuiBox from '@/components/sui/sui-box'
import PieChart from '@/components/charts/pie-chart'

export default {
	name: 'Mediums',

	components: {
		SuiBox,
		PieChart,
	},

	mixins: [Pie],

	data() {
		return {
			type: 'mediums',
		}
	},
}
</script>
