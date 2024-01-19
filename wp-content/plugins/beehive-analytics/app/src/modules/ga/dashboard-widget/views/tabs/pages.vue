<template>
	<div
		role="tabpanel"
		tabindex="0"
		id="beehive-widget-content--pages"
		class="sui-tab-content"
		aria-labelledby="beehive-widget-tab--pages"
		:hidden="'pages' !== activeTab"
		:class="{ active: 'pages' === activeTab }"
	>
		<sui-notice v-if="canGetStats && isEmpty" type="info">
			<p>{{ $i18n.notice.empty_data }}</p>
		</sui-notice>

		<sui-notice v-else-if="!canGetStats && !isConnected" type="error">
			<p v-html="$i18n.notice.auth_required"></p>
		</sui-notice>

		<table class="beehive-table" v-else>
			<thead>
				<tr>
					<th colspan="3">
						{{ $i18n.label.top_pages_most_visited }}
					</th>
					<th class="beehive-column-time">
						{{ $i18n.label.average_sessions }}
					</th>
					<th class="beehive-column-views">
						{{ $i18n.label.views }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(item, key) in stats.pages" :key="key">
					<td colspan="3" v-html="item[0]"></td>
					<td class="beehive-column-time">{{ item[1] }}</td>
					<td class="beehive-column-views" v-if="item[3] < 0">
						{{ item[2] }}
						<i
							class="sui-icon-arrow-down sui-sm beehive-red"
							aria-hidden="true"
						></i>
					</td>
					<td class="beehive-column-views" v-else-if="item[3] > 0">
						{{ item[2] }}
						<i
							class="sui-icon-arrow-up sui-sm beehive-green"
							aria-hidden="true"
						></i>
					</td>
					<td class="beehive-column-views" v-else>
						{{ item[2] }}
						<span class="empty-space"></span>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import SuiNotice from '@/components/sui/sui-notice'

export default {
	name: 'Pages',

	props: ['stats', 'activeTab'],

	components: { SuiNotice },

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
			return Object.keys(this.stats).length <= 0 || !this.stats.pages
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
	},
}
</script>
