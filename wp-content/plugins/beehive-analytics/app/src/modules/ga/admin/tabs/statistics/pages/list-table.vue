<template>
	<table class="beehive-table-pages">
		<thead>
			<tr>
				<th colspan="2">
					{{ $i18n.label.top_pages_most_visited }}
				</th>
				<th>{{ $i18n.label.average_sessions }}</th>
				<th>{{ $i18n.label.views }}</th>
				<th>{{ $i18n.label.trend }}</th>
			</tr>
		</thead>
		<tbody>
			<tr v-for="(item, key) in getPages" :key="key">
				<td colspan="2" v-html="item[0]"></td>
				<td>{{ item[1] }}</td>
				<td>{{ item[2] }}</td>
				<td class="beehive-red" v-if="item[3] < 0">
					<i
						class="sui-icon-arrow-down sui-sm"
						aria-hidden="true"
					></i>
					{{ item[3] }}%
				</td>
				<td class="beehive-green" v-else-if="item[3] > 0">
					<i class="sui-icon-arrow-up sui-sm" aria-hidden="true"></i>
					{{ item[3] }}%
				</td>
				<td class="beehive-green" v-else>0%</td>
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
		 * Get the list of pages with data.
		 *
		 * Only the top 10 pages are required.
		 *
		 * @since 3.3.5
		 *
		 * @return {array}
		 */
		getPages() {
			let pages = []

			if (this.stats.pages) {
				pages = this.stats.pages.slice(0, 9)
			}

			return pages
		},
	},
}
</script>
