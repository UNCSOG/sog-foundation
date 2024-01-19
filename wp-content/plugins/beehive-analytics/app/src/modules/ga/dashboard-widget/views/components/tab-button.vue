<template>
	<button
		v-if="canView"
		type="button"
		role="tab"
		class="sui-tab-item"
		:id="`beehive-widget-tab--${name}`"
		:aria-controls="`beehive-widget-content--${name}`"
		:tabindex="isSelected ? false : '-1'"
		:aria-selected="isSelected ? 'true' : 'false'"
		:class="{ active: isSelected }"
	>
		{{ label }}
	</button>
</template>

<script>
import { canViewStats } from '@/helpers/utils'

export default {
	name: 'TabButton',

	props: {
		name: String,
		label: String,
		defaultTab: String,
	},

	computed: {
		/**
		 * Check if current tab item is selected.
		 *
		 * @since 3.3.8
		 *
		 * @return {boolean}
		 */
		isSelected() {
			return this.name === this.defaultTab
		},

		/**
		 * Check if current user can view this tab.
		 *
		 * @since 3.3.8
		 *
		 * @return {string|boolean}
		 */
		canView() {
			return canViewStats(this.name, 'dashboard')
		},
	},
}
</script>
