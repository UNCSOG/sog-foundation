<template>
	<div
		class="sui-accordion-item"
		:class="accordionClass"
		:key="role"
		:ref="`permissionItem${role}`"
	>
		<div class="sui-accordion-item-header">
			<div class="sui-accordion-item-title">
				<label
					:for="`beehive-settings-permissions-roles-${role}`"
					class="sui-toggle sui-accordion-item-action"
				>
					<input
						v-model="enabledRoles"
						type="checkbox"
						:id="`beehive-settings-permissions-roles-${role}`"
						:value="role"
						:disabled="overwrite"
						@change="roleStatusChange"
					/>
					<span aria-hidden="true" class="sui-toggle-slider"></span>
					<span class="sui-screen-reader-text">{{ title }}</span>
					<span class="sui-toggle-label">{{ title }}</span>
				</label>
			</div>
			<div class="sui-accordion-col-auto">
				<button
					class="sui-button-icon sui-accordion-open-indicator"
					:aria-label="$i18n.accordion.open"
				>
					<i class="sui-icon-chevron-down" aria-hidden="true"></i>
				</button>
			</div>
		</div>
		<div class="sui-accordion-item-body">
			<div class="sui-box">
				<div class="sui-box-body">
					<div
						class="sui-form-field"
						v-for="(report, type) in getReportItems"
						:key="type"
					>
						<report-tree
							:role="role"
							:type="report.name"
							:title="report.title"
							:items="report.children"
						/>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import ReportTree from './report-tree'

export default {
	name: 'RoleAccordionItem',

	components: { ReportTree },

	props: {
		title: {
			type: String,
			required: true,
		},
		role: {
			type: String,
			required: true,
		},
		overwrite: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			open: false,
		}
	},

	computed: {
		/**
		 * Computed object to handle enabled roles permissions.
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
		 * Get the report tree object.
		 *
		 * @since 3.2.5
		 *
		 * @returns {object}
		 */
		getReportItems() {
			return this.$moduleVars.report_tree || {}
		},

		/**
		 * Check if the current role is enabled.
		 *
		 * @since 3.3.2
		 *
		 * @returns {boolean}
		 */
		enabled() {
			return this.enabledRoles.includes(this.role)
		},

		/**
		 * Set the accordion class based on the role.
		 *
		 * @since 3.2.5
		 *
		 * @returns {*}
		 */
		accordionClass() {
			return {
				'sui-accordion-item--open': this.open,
				'sui-accordion-item--disabled': !this.enabled || this.overwrite,
			}
		},
	},

	methods: {
		/**
		 * Handle when a role status is changed.
		 *
		 * @since 1.8.1
		 *
		 * @returns {void}
		 */
		roleStatusChange() {
			this.open = this.enabled
		},
	},
}
</script>
