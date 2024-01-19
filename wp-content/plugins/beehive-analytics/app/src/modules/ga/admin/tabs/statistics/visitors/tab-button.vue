<template>
	<button
		class="beehive-tab"
		aria-controls="beehive-visitors-chart"
		:key="name"
		:class="tabClass"
		:aria-selected="ariaSelected"
		:tabindex="tabIndex"
		@click="$emit('changeTab', name)"
	>
		<span class="beehive-tab-title" aria-hidden="true">
			{{ getTitle }}
		</span>

		<span class="beehive-tab-value-wrap" aria-hidden="true">
			<span class="beehive-tab-value" v-if="'bounce_rates' === name"
				>{{ item.value }}%</span
			>
			<span class="beehive-tab-value" v-else>
				{{ item.value }}
			</span>

			<span
				class="beehive-tab-trend beehive-green"
				v-if="item.trend < 0 && 'bounce_rates' === name"
			>
				<i class="sui-icon-arrow-down sui-sm" aria-hidden="true"></i>
				{{ Math.abs(item.trend) }}%
			</span>
			<span
				class="beehive-tab-trend beehive-red"
				v-else-if="item.trend < 0"
			>
				<i class="sui-icon-arrow-down sui-sm" aria-hidden="true"></i>
				{{ Math.abs(item.trend) }}%
			</span>
			<span
				class="beehive-tab-trend beehive-red"
				v-else-if="item.trend > 0 && 'bounce_rates' === name"
			>
				<i class="sui-icon-arrow-up sui-sm" aria-hidden="true"></i>
				{{ Math.abs(item.trend) }}%
			</span>
			<span
				class="beehive-tab-trend beehive-green"
				v-else-if="item.trend > 0"
			>
				<i class="sui-icon-arrow-up sui-sm" aria-hidden="true"></i>
				{{ Math.abs(item.trend) }}%
			</span>
		</span>

		<span class="sui-screen-reader-text">{{ getTitle }}</span>
	</button>
</template>

<script>
export default {
	name: 'TabButton',

	props: {
		name: String,
		item: Object,
		selected: String,
		sections: Object,
	},

	computed: {
		/**
		 * Get tab button active class.
		 *
		 * @since 3.3.5
		 *
		 * @returns {*}
		 */
		tabClass() {
			return {
				'beehive-active': this.selected === this.name,
			}
		},

		/**
		 * Get the aria-selected attribute value.
		 *
		 * @since 3.3.5
		 *
		 * @returns {object}
		 */
		ariaSelected() {
			// Should be string.
			return this.name === this.selected ? 'true' : 'false'
		},

		/**
		 * Get the tab-index attribute value.
		 *
		 * @since 3.3.5
		 *
		 * @returns {object}
		 */
		tabIndex() {
			// Should be string.
			return this.name === this.selected ? -1 : false
		},

		/**
		 * Get the title of the item.
		 *
		 * @since 3.2.4
		 *
		 * @returns {string}
		 */
		getTitle() {
			return this.sections[this.name].title
		},
	},
}
</script>
