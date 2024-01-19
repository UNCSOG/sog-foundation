<template>
	<sui-box
		class="beehive-widget"
		titleIcon="gtm"
		:title="$i18n.title.ga_box"
	>
		<template v-slot:body>
			<p>{{ $i18n.desc.ga_box }}</p>
		</template>
		<template v-slot:outside>
			<table class="sui-table beehive-outside-table">
				<tbody>
				<config-row :label="$i18n.label.ga4" :status="getGA4Status"/>
				</tbody>
				<tfoot>
				<tr>
					<td colspan="3">
						<a
							href="https://wpmudev.com/docs/wpmu-dev-plugins/beehive/#account-analytics"
							target="_blank"
							class="sui-button sui-button-ghost"
						>
							<span class="sui-icon-open-new-window" aria-hidden="true"></span>
							{{ $i18n.button.learn_more }}
						</a>
					</td>
				</tr>
				</tfoot>
			</table>
		</template>
	</sui-box>
</template>

<script>
import ConfigRow from './ga/config-row'
import SuiBox from '@/components/sui/sui-box'

export default {
	name: 'GaWidget',

	components: { SuiBox, ConfigRow },

	computed: {
		/**
		 * Check if the current user is logged in with Google.
		 *
		 * @since 3.2.4
		 *
		 * @returns {boolean}
		 */
		isConnected() {
			return this.$store.state.helpers.google.logged_in
		},

		/**
		 * Get the GA4 account status.
		 *
		 * @since 3.4.0
		 *
		 * @returns {string}
		 */
		getGA4Status() {
			// Check if account setup.
			let setup  = !!this.getOption('stream', 'google')
			// If logged in but account is not setup.
			if ( this.isConnected && ! setup ) {
				return 'error'
			} else {
				return setup ? 'active' : 'inactive'
			}
		},
	},
}
</script>
