<template>
	<div class="google-account-selector">
		<profiles
			id="beehive-settings-google-account-id"
			:label="$i18n.label.choose_account"
			:show-desc="isConnected"
		/>
		<!-- When there is no profiles found -->
		<sui-notice v-if="emptyProfiles" type="error">
			<p v-html="$i18n.notice.no_accounts"></p>
		</sui-notice>
	</div>
</template>

<script>
import Profiles from './fields/profiles'
import SuiNotice from '@/components/sui/sui-notice'

export default {
	name: 'ProfilesConnected',

	components: { Profiles, SuiNotice },

	computed: {
		/**
		 * Check if Google account is connected.
		 *
		 * @since 3.3.0
		 *
		 * @returns {boolean}
		 */
		isConnected() {
			return this.$store.state.helpers.google.logged_in
		},

		/**
		 * Check if Google profile list is empty.
		 *
		 * @since 3.3.0
		 *
		 * @returns {boolean}
		 */
		emptyProfiles() {
			return this.$store.state.helpers.google.profiles.length <= 0
		},
	},
}
</script>
