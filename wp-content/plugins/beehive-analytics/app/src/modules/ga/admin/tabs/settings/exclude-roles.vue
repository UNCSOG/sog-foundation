<template>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				{{ $i18n.label.exclude_roles_tracking }}
			</span>
			<span class="sui-description">
				{{ $i18n.desc.exclude_roles_tracking }}
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label class="sui-label" for="beehive-settings-exclude-roles">
					{{ $i18n.label.user_roles }}
				</label>
				<sui-select2
					id="beehive-settings-exclude-roles"
					v-model="excludedRoles"
					:options="getRoleOptions"
					:multiple="true"
				/>
			</div>
		</div>
	</div>
</template>

<script>
import SuiSelect2 from '@/components/sui/sui-select2'

export default {
	name: 'ExcludeRoles',

	components: { SuiSelect2 },

	computed: {
		/**
		 * Computed object to get the excluded roles option.
		 *
		 * @since 3.3.6
		 *
		 * @returns {[]}
		 */
		excludedRoles: {
			get() {
				return this.getOption('exclude_roles', 'tracking', [])
			},
			set(value) {
				this.setOption('exclude_roles', 'tracking', value)
			},
		},

		/**
		 * Get roles in Select2 format.
		 *
		 * @since 3.3.6
		 *
		 * @return {[]}
		 */
		getRoleOptions() {
			let options = []
			let roles = this.$moduleVars.roles

			Object.keys(roles).forEach((role) => {
				options.push({
					id: role,
					text: roles[role],
				})
			})

			return options
		},
	},
}
</script>
