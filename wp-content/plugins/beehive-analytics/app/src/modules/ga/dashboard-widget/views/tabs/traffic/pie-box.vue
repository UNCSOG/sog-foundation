<template>
	<fragment>
		<div class="beehive-col">
			<div class="beehive-box">
				<h3 class="beehive-box-title" v-html="getBoxTitle"></h3>
				<div class="beehive-box-content">
					<p class="beehive-box-stat-value">
						{{ getStatValue + '%' }}
					</p>
					<p class="beehive-box-stat-name">
						{{ getStatName }}
					</p>
					<sui-score :value="parseInt(getStatValue)" />
				</div>
			</div>
		</div>
		<span
			class="beehive-separator"
			aria-hidden="true"
			v-if="'social_networks' !== name"
		/>
	</fragment>
</template>

<script>
import SuiScore from '@/components/sui/sui-score'

export default {
	name: 'PieBox',

	props: {
		stats: Array | Object,
		item: Object,
		name: String,
	},

	components: {
		SuiScore,
	},

	computed: {
		/**
		 * Get the box title text.
		 *
		 * @since 3.3.8
		 *
		 * @return {string}
		 */
		getBoxTitle() {
			let icon = '',
				item = this.item,
				title = this.item.title

			if (!title || '' === title) {
				return ''
			}

			if (item.icon && '' !== item.icon) {
				icon =
					'<i class="sui-icon-' +
					item.icon +
					' sui-md" aria-hidden="true"></i>'
			}

			return icon + title
		},

		/**
		 * Get current item stats value.
		 *
		 * This value is the percentage of the value.
		 *
		 * @since 3.3.8
		 *
		 * @return {number|decimal|string}
		 */
		getStatValue() {
			const self = this
			let name = this.name

			let percent = 0
			let value = 0

			const sign = percent >= 0 ? 1 : -1
			const decimal = 0

			if ((null !== typeof name || '' !== name) && this.stats[name]) {
				Object.keys(self.stats[name]).forEach(function (k) {
					value += parseInt(self.stats[name][k][1])
				})

				percent = (parseInt(self.stats[name][0][1]) * 100) / value

				if (percent >= 0) {
					percent = (
						Math.round(
							percent * Math.pow(10, decimal) + sign * 0.0001
						) / Math.pow(10, decimal)
					).toFixed(decimal)
				}
			}

			return percent
		},

		/**
		 * Get the title for the stats item.
		 *
		 * @since 3.3.8
		 *
		 * @return {*}
		 */
		getStatName() {
			let name = this.name
			let value = this.$i18n.label.none

			if ((null !== typeof name || '' !== name) && this.stats[name]) {
				value = this.stats[name][0][0]
			}

			return value
		},
	},
}
</script>
