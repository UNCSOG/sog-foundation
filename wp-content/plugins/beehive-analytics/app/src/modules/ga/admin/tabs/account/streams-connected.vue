<template>
	<div class="google-account-selector">
		<streams
			id="beehive-settings-google-stream-id"
			:label="$i18n.label.choose_stream"
			:show-desc="isConnected"
		/>
		<!-- When there is no profiles found -->
		<sui-notice v-if="emptyStreams" type="error">
			<p v-html="$i18n.notice.no_streams"></p>
		</sui-notice>
		<sui-notice v-else-if="showLimitNotice" type="info">
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
		 * Check if Google stream list is empty.
		 *
		 * @since 3.4.0
		 *
		 * @returns {boolean}
		 */
		emptyStreams() {
			return this.$store.state.helpers.google.streams.length <= 0
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
}
</script>
