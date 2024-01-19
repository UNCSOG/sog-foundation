<template>
	<tr>
		<td colspan="3" class="beehive-column-country">
			<div class="beehive-country-content">
				<span :class="flagClass" aria-hidden="true"></span>
				<p class="beehive-country-name">
					{{ stats[0] }}
				</p>
				<div class="beehive-country-percent" aria-hidden="true">
					<span :style="percentageStyle"></span>
				</div>
			</div>
		</td>
		<td class="beehive-column-views">{{ stats[2] }}</td>
	</tr>
</template>

<script>
export default {
	name: 'TableRow',

	props: {
		stats: Array | Object,
		total: Number,
	},

	computed: {
		/**
		 * Get classes for country flag.
		 *
		 * @since 3.3.8
		 *
		 * @return {*}
		 */
		flagClass() {
			// Get country specific class.
			let countryClass = 'beehive-flag-' + this.stats[1]

			return {
				'beehive-flag': true,
				'beehive-country-flag': true,
				[countryClass]: true,
			}
		},

		/**
		 * Get the style object for percent disply.
		 *
		 * @since 3.3.8
		 *
		 * @return {{width: string}}
		 */
		percentageStyle() {
			// Get percent value.
			let percent = (parseInt(this.stats[2]) * 100) / this.total

			return {
				width: percent + '%',
			}
		},
	},
}
</script>
