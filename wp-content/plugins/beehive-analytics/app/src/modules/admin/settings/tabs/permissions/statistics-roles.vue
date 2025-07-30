<template>
	<div class="sui-accordion" id="beehive-settings-permissions-roles">
		<role-accordion-item
			:key="role"
			:role="role"
			:title="title"
			:overwrite="overwriteCap"
			v-for="(title, role) in roles"
		/>
	</div>
</template>

<script>
import ReportTree from './components/report-tree'
import RoleAccordionItem from './components/role-accordion-item'

export default {
	name: 'StatisticsRoles',

	components: {
		ReportTree,
		RoleAccordionItem,
	},

	mounted() {
		// Initialize accordion.
		SUI.suiAccordion(jQuery('#beehive-settings-permissions-roles'))
	},

	data() {
		return {
			roles: this.$moduleVars.roles,
		}
	},

	computed: {
		/**
		 * Computed object to habdle enabled roles permissions.
		 *
		 * @since 3.2.5
		 *
		 * @returns {array}
		 */
		enabledRoles: {
			get() {
				return this.getOption('roles', 'permissions', [])
			},
			set(value) {
				this.setOption('roles', 'permissions', value)
			},
		},

		/**
		 * Check if we can override the settings.
		 *
		 * @since 3.2.5
		 *
		 * @returns {boolean}
		 */
		overwriteCap() {
			return this.getOption('overwrite_cap', 'permissions')
		},
	},
}
</script>
