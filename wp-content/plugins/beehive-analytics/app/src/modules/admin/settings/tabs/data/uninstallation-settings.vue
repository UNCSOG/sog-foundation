<template>
	<div class="sui-form-field">
		<h4 class="sui-settings-label">
			{{ $i18n.label.settings }}
		</h4>
		<span class="sui-description beehive-margin-bottom--10">
			{{ $i18n.desc.uninstallation_settings }}
		</span>
		<div class="sui-side-tabs">
			<div class="sui-tabs-menu">
				<sui-tab-radio
					id="uninstallation-data-settings-preserve"
					:option="true"
					:label="$i18n.label.preserve"
					v-model="settingsClear"
				/>
				<sui-tab-radio
					id="uninstallation-data-settings-reset"
					:option="false"
					:label="$i18n.label.reset"
					v-model="settingsClear"
				/>
			</div>
		</div>
	</div>
</template>

<script>
import SuiTabRadio from '@/components/sui/sui-tab-radio'

export default {
	name: 'UninstallationSettings',

	components: { SuiTabRadio },

	computed: {
		/**
		 * Computed model object to manage settings clear settings.
		 *
		 * @since 3.3.5
		 *
		 * @returns {string}
		 */
		settingsClear: {
			get() {
				return this.getOption('settings', 'data', false)
			},
			set(value) {
				this.setOption('settings', 'data', value)
			},
		},
	},
}
</script>
