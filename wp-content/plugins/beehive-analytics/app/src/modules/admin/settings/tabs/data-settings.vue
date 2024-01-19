<template>
	<div class="sui-box" id="beehive-settings-data-settings">
		<box-header :title="$i18n.title.data_settings" />
		<div class="sui-box-body">
			<div class="sui-box-settings-row">
				<div class="sui-box-settings-col-2">
					<p>{{ $i18n.desc.data_settings }}</p>
				</div>
			</div>
			<div class="sui-box-settings-row" v-if="showCleanup">
				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label">
						{{ $i18n.label.uninstallation }}
					</span>
					<span class="sui-description">
						{{ $i18n.desc.uninstallation }}
					</span>
				</div>
				<div class="sui-box-settings-col-2">
					<uninstallation-settings />
				</div>
			</div>
			<div class="sui-box-settings-row">
				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label">
						{{ $i18n.label.reset_settings }}
					</span>
					<span class="sui-description">
						{{ $i18n.desc.reset_settings }}
					</span>
				</div>
				<div class="sui-box-settings-col-2">
					<reset-settings />
					<p class="sui-description">
						{{ $i18n.desc.reset_settings_sub }}
					</p>
				</div>
			</div>
		</div>
		<box-footer :processing="processing" @submit="$emit('submit')" />
	</div>
</template>

<script>
import ResetSettings from './data/reset-settings'
import BoxHeader from '@/components/elements/box-header'
import BoxFooter from '@/components/elements/box-footer'
import SuiTabRadio from '@/components/sui/sui-tab-radio'
import UninstallationSettings from './data/uninstallation-settings'

export default {
	name: 'DataSettings',

	components: {
		BoxHeader,
		BoxFooter,
		SuiTabRadio,
		ResetSettings,
		UninstallationSettings,
	},

	props: {
		processing: {
			type: Boolean,
			default: false,
		},
	},

	mounted() {
		SUI.tabs()
		SUI.suiTabs()
	},

	computed: {
		/**
		 * Check if uninstall cleanup option is required.
		 *
		 * On multisite, do not show this in subsites.
		 *
		 * @since 3.3.5
		 *
		 * @returns {boolean}
		 */
		showCleanup() {
			return !this.isSubsite()
		},
	},
}
</script>
