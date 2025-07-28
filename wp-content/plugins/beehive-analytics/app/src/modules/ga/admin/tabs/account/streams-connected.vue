<template>
	<div class="google-account-selector">
		<streams @validate="validateStream" />
		<sui-notice v-if="error.property || error.stream" type="error">
			<p v-if="error.stream" v-html="$i18n.notice.invalid_stream_id"></p>
			<p
				v-if="error.property"
				v-html="$i18n.notice.invalid_propery_id"
			></p>
		</sui-notice>
		<sui-notice v-if="showLimitNotice" type="info">
			<p v-html="$i18n.notice.limit_notice"></p>
		</sui-notice>
		<div v-if="showAutoTrack" class="sui-form-field">
			<label
				for="beehive-settings-ga4-auto-track"
				class="sui-checkbox sui-checkbox-sm"
			>
				<input
					v-model="autoTrack"
					type="checkbox"
					id="beehive-settings-ga4-auto-track"
					value="1"
				/>
				<span aria-hidden="true"></span>
				<span>
					{{ $i18n.label.auto_detect_measurement }}
					<span
						class="sui-tooltip sui-tooltip-constrained"
						:data-tooltip="$i18n.tooltip.measurement_id"
					>
						<i class="sui-icon-info" aria-hidden="true"></i>
					</span>
				</span>
			</label>
		</div>
	</div>
</template>

<script>
import Streams from './fields/streams'
import Profiles from './fields/profiles'
import SuiNotice from '@/components/sui/sui-notice'

export default {
	name: 'StreamsConnected',

	components: { Profiles, Streams, SuiNotice },
	data() {
		return {
			error: {
				property: false,
				stream: false,
			},
		}
	},
	computed: {
		/**
		 * Computed model object to get auto measurement ID.
		 *
		 * @since 3.4.0
		 *
		 * @returns {string}
		 */
		autoTrack: {
			get() {
				return this.getOption('auto_track_ga4', 'google')
			},
			set(value) {
				this.setOption('auto_track_ga4', 'google', value)
			},
		},

		/**
		 * Computed method to check if auto tracking is enabled.
		 *
		 * @since 3.4.0
		 *
		 * @returns {boolean}
		 */
		showAutoTrack() {
			let autoTrack = this.getOption('auto_track_ga4', 'misc')

			return autoTrack && autoTrack !== ''
		},

		/**
		 * Check if Google account is connected.
		 *
		 * @since 3.4.0
		 *
		 * @returns {boolean}
		 */
		isConnected() {
			return this.$store.state.helpers.google.logged_in
		},

		/**
		 * Check if account limit notice should be visible.
		 *
		 * @since 3.4.8
		 *
		 * @returns {boolean}
		 */
		showLimitNotice() {
			return this.$store.state.helpers.google.show_limit_notice
		},
	},
	methods: {
		/**
		 * Validate the Google Analytics stream selection.
		 *
		 * @since 3.4.16
		 */
		validateStream() {
			const account = this.getOption('account', 'google')
			const property = this.getOption('property', 'google')
			const stream = this.getOption('stream', 'google')

			this.error.property = false
			this.error.stream = false

			if (account && (!property || !stream)) {
				if (!property) {
					this.error.property = true
				} else {
					this.error.stream = true
				}
			}

			this.$emit('validate', account && property && stream)
		},
	},
}
</script>
