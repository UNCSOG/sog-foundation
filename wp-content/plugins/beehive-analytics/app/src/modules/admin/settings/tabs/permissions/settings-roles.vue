<template>
	<div class="sui-form-field">
		<label
			v-for="(title, role) in roles"
			:key="role"
			:for="`beehive-settings-permissions-user-role-${role}`"
			class="sui-checkbox sui-checkbox-stacked"
		>
			<input
				type="checkbox"
				v-model="enabledRoles"
				:id="`beehive-settings-permissions-user-role-${role}`"
				:aria-labelledby="`beehive-settings-permissions-user-role-${role}-label`"
				:value="role"
				:disabled="shouldDisable(role)"
			/>
			<span aria-hidden="true"></span>
			<span :id="`beehive-settings-permissions-user-role-${role}-label`">
				{{ title }}
			</span>
		</label>
	</div>
</template>

<script>
export default {
	name: 'SettingsRoles',

	data() {
		return {
			roles: this.$moduleVars.roles,
		}
	},

	computed: {
		/**
		 * Computed model for the settings role option.
		 *
		 * @since 3.2.5
		 *
		 * @returns {array}
		 */
		enabledRoles: {
			get() {
				return this.getOption('settings_roles', 'permissions', [])
			},
			set(value) {
				this.setOption('settings_roles', 'permissions', value)
			},
		},
	},
	methods: {
		/**
		 * Check if we need to disable the settings.
		 *
		 * If network admin allow overriding in subsites,
		 * we need to disable the options for the super admin.
		 *
		 * @since 3.2.5
		 *
		 * @returns {boolean}
		 */
		shouldDisable(role) {
			return (
				(this.inNetworkAdmin() && role === 'super_admin') ||
				(this.inSubsiteAdmin() && role === 'administrator') ||
				(this.isNetwork() &&
					this.getOption('overwrite_settings_cap', 'permissions'))
			)
		},
	},
}
</script>
