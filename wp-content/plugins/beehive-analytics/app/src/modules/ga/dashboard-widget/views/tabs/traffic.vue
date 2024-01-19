<template>
	<div
		role="tabpanel"
		tabindex="0"
		id="beehive-widget-content--traffic"
		class="sui-tab-content"
		aria-labelledby="beehive-widget-tab--traffic"
		:hidden="'traffic' !== activeTab"
		:class="{ active: 'traffic' === activeTab }"
	>
		<sui-notice v-if="canGetStats && isEmpty" type="info">
			<p>{{ $i18n.notice.empty_data }}</p>
		</sui-notice>

		<sui-notice v-else-if="!canGetStats && !isConnected" type="error">
			<p v-html="$i18n.notice.auth_required"></p>
		</sui-notice>

		<fragment v-else>
			<table class="beehive-table">
				<thead>
					<tr>
						<th colspan="3" class="beehive-column-country">
							{{ $i18n.label.top_countries }}
						</th>
						<th class="beehive-column-views">
							{{ $i18n.label.views }}
						</th>
					</tr>
				</thead>
				<tbody>
					<table-row
						v-for="(item, key) in stats.countries"
						:key="key"
						:stats="item"
						:total="getTotalValue"
					/>
				</tbody>
			</table>
			<div class="beehive-row">
				<pie-box
					v-for="(item, key) in summary"
					:key="key"
					:name="key"
					:stats="stats"
					:item="item"
				/>
			</div>
		</fragment>
	</div>
</template>

<script>
import PieBox from './traffic/pie-box'
import TableRow from './traffic/table-row'
import SuiScore from '@/components/sui/sui-score'
import SuiNotice from '@/components/sui/sui-notice'

export default {
	name: 'Traffic',

	props: {
		stats: Object | Array,
		activeTab: String,
	},

	components: {
		PieBox,
		TableRow,
		SuiScore,
		SuiNotice,
	},

	data() {
		return {
			summary: {
				search_engines: {
					title: this.$i18n.label.top_search_engine,
					icon: 'magnifying-glass-search',
				},
				mediums: {
					title: this.$i18n.label.top_medium,
					icon: 'update',
				},
				social_networks: {
					title: this.$i18n.label.top_social_network,
					icon: 'community-people',
				},
			},
		}
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
			return Object.keys(this.stats).length <= 0
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
		 * Get the total value of the traffic stats.
		 *
		 * @since 3.3.8
		 *
		 * @return {number}
		 */
		getTotalValue() {
			const self = this

			let topValue = 0

			// Add each items.
			Object.keys(this.stats.countries).forEach(function (key) {
				topValue += parseInt(self.stats.countries[key][2])
			})

			return topValue
		},
	},
}
</script>
