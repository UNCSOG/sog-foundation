<template>
	<div class="sui-form-field">
		<!-- Account Selector -->
		<BaseSelector
			:label="$i18n.label.choose_ga_account"
			:placeholder="$i18n.placeholder.account_id"
			endpoint="accounts"
			@change="handleAccountSelect"
		/>

		<!-- Property Selector -->
		<BaseSelector
			:label="$i18n.label.choose_ga_property"
			:placeholder="$i18n.placeholder.property_id"
			endpoint="properties"
			parent-key="account"
			:parent-value="selectedAccount"
			@change="handlePropertySelect"
		/>

		<!-- Stream Selector -->
		<BaseSelector
			:label="$i18n.label.choose_stream"
			:placeholder="$i18n.placeholder.stream_id"
			endpoint="streams"
			parent-key="property"
			:parent-value="selectedProperty"
			@change="handleStreamSelect"
		/>
	</div>
</template>

<script>
import BaseSelector from '@/modules/ga/admin/tabs/account/fields/base-selector.vue'

export default {
	components: { BaseSelector },

	data() {
		return {
			selectedAccount: null,
			selectedProperty: null,
		}
	},

	methods: {
		handleAccountSelect({ id, text }) {
			this.selectedAccount = id
			this.selectedProperty = null // Reset dependent selector
			this.updateOptions('account', id, text)
			this.resetOptions(['property', 'stream'])
			this.$emit('validate')
		},

		handlePropertySelect({ id, text }) {
			this.selectedProperty = id
			this.updateOptions('property', id, text)
			this.resetOptions(['stream'])
			this.$emit('validate')
		},

		handleStreamSelect({ id, text, measurement, url }) {
			this.updateOptions('stream', id, text)
			const currentUrl = window.location.origin
			const normalizeUrl = (url) =>
				url.replace(/^(http:\/\/|https:\/\/)/, '').replace(/\/$/, '')

			if (normalizeUrl(currentUrl) === normalizeUrl(url)) {
				this.setOption('auto_track_ga4', 'misc', measurement)
			} else {
				this.setOption('auto_track_ga4', 'misc', '')
			}

			this.$store.dispatch('helpers/updateCanGetStats', !!id)
			this.$emit('validate')
		},

		updateOptions(type, id, text) {
			this.setOption(type, 'google', id)
			this.setOption(type, 'misc', { id, text })
		},

		resetOptions(types) {
			types.forEach((type) => {
				this.setOption(type, 'google', '')
				this.setOption(type, 'misc', false)
			})
		},
	},
}
</script>
