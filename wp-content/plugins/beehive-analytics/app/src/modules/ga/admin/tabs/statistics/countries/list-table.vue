<template>
	<table class="beehive-chart-map-legend">
		<thead>
			<tr>
				<th>{{ $i18n.label.country_code }}</th>
				<th>{{ $i18n.label.country_name }}</th>
				<th>{{ $i18n.label.visits_percentage }}</th>
				<th>{{ $i18n.label.total_visits }}</th>
			</tr>
		</thead>

		<tbody>
			<tr
				class="beehive-legend-item"
				v-for="(item, name) in getCountries"
				:key="name"
			>
				<td class="beehive-legend-item-flag">
					<span class="sui-screen-reader-text">
						{{ item[1] }}
					</span>
					<span
						:class="'beehive-flag beehive-flag-' + item[1]"
						aria-hidden="true"
					></span>
				</td>

				<td class="beehive-legend-item-name">
					{{ item[0] }}
				</td>

				<td class="beehive-legend-item-bar">
					<span class="sui-screen-reader-text">
						{{
							sprintf(
								$i18n.desc.percentage_visits,
								visitsPercent(item[2]) + '%'
							)
						}}
					</span>
					<span aria-hidden="true">
						<span
							:style="'width: ' + visitsPercent(item[2]) + '%;'"
						></span>
					</span>
				</td>

				<td class="beehive-legend-item-value">
					{{ item[2] }}
				</td>
			</tr>
		</tbody>
	</table>
</template>

<script>
export default {
	name: 'ListTable',

	props: {
		stats: Object,
	},

	computed: {
		/**
		 * Get the list of countries with data.
		 *
		 * We need only the top 5 items here.
		 *
		 * @since 3.3.5
		 *
		 * @return {array}
		 */
		getCountries() {
			let countries = []

			if (this.stats.countries) {
				countries = this.stats.countries.slice(0, 5)
			}

			return countries
		},
	},

	methods: {
		/**
		 * Calculate and get the visit percentage.
		 *
		 * @param {int|string} value Value.
		 *
		 * @since 3.3.5
		 *
		 * @returns {number}
		 */
		visitsPercent(value) {
			const self = this

			let topValue = 0

			Object.keys(this.stats.countries).forEach(function (key) {
				topValue += parseInt(self.stats.countries[key][2])
			})

			return (parseInt(value) * 100) / topValue
		},
	},
}
</script>
