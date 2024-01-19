<template>
	<div class="google-account-selector">
		<div class="sui-form-field">
			<label for="beehive-settings-google-stream-id" class="sui-label">
				{{ $i18n.label.choose_stream }}
			</label>
			<sui-select2
				id="beehive-settings-google-stream-id"
				v-model="profile"
				:options="[]"
				:placeholder="$i18n.placeholder.no_website"
				:disabled="true"
			/>
		</div>
		<!-- When not authenticated -->
		<sui-notice type="info">
			<p
				v-html="
					sprintf(
						$i18n.notice.authentication_required,
						$vars.urls.accounts
					)
				"
			></p>
		</sui-notice>
	</div>
</template>

<script>
import Profiles from './fields/profiles'
import SuiNotice from '@/components/sui/sui-notice'
import SuiSelect2 from '@/components/sui/sui-select2'

export default {
	name: 'StreamsDisconnected',

	components: { Profiles, SuiNotice, SuiSelect2 },

	data() {
		return {
			profile: '',
		}
	},
}
</script>
