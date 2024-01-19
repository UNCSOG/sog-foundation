<template>
	<div class="sui-box">
		<box-header :title="$i18n.label.account" />
		<div class="sui-box-body">
			<p>{{ $i18n.desc.account }}</p>

			<!-- API status notice -->
			<sui-notice v-if="getApiErrorMessage" type="error">
				<p>{{ sprintf( $i18n.notice.api_error, getApiErrorMessage ) }}</p>
			</sui-notice>

			<!-- Account setup notice -->
			<sui-notice type="info" v-if="showAccountNotice">
				<p
					v-html="
						sprintf(
							$i18n.notice.account_setup_login,
							$vars.urls.statistics
						)
					"
				></p>
			</sui-notice>

			<div class="sui-box-settings-slim-row">
				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label">
						{{ $i18n.label.analytics_4_profile }}
					</span>
					<span class="sui-description">
						{{ $i18n.desc.analytics_4_profile }}
					</span>
				</div>
				<div class="sui-box-settings-col-2">
					<!-- Profile/View selection -->
					<streams-connected v-if="isConnected" />
					<streams-disconnected v-else />
				</div>
			</div>

			<div class="sui-box-settings-row">
				<div class="sui-box-settings-col-1">
					<span v-if="isNetwork()" class="sui-settings-label">
						{{ $i18n.label.network_measurement_id }}
					</span>
					<span v-else class="sui-settings-label">
						{{ $i18n.label.measurement_id }}
					</span>
					<span class="sui-description">
						{{ $i18n.desc.analytics_4 }}
					</span>
					<span v-if="isNetwork()" class="sui-description">
						{{ $i18n.desc.network_measurement_id }}
					</span>
					<span v-else class="sui-description">
						{{ $i18n.desc.measurement_id }}
					</span>
				</div>
				<div class="sui-box-settings-col-2">
					<!-- Show automatic measurement id -->
					<measurement-automatic v-if="showAutoMeasurement"/>
					<!-- Show manual measurement id -->
					<measurement-manual
						:error="error.measurement"
						@validation="measurementValidation"
						v-else
					/>
				</div>
			</div>

			<div class="sui-box-settings-slim-row">
				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label">
						{{ $i18n.label.analytics_profile }}
					</span>
					<span class="sui-description">
						{{ $i18n.desc.analytics_profile }}
					</span>
				</div>
				<div class="sui-box-settings-col-2">
					<!-- Profile/View selection -->
					<profiles-connected v-if="isConnected" />
					<profiles-disconnected v-else />
				</div>
			</div>
		</div>
		<box-footer :processing="processing" @submit="formSubmit" />
	</div>
</template>

<script>
import SuiNotice from '@/components/sui/sui-notice'
import BoxHeader from '@/components/elements/box-header'
import BoxFooter from '@/components/elements/box-footer'
import StreamsConnected from './account/streams-connected'
import MeasurementManual from './account/measurement-manual'
import ProfilesConnected from './account/profiles-connected'
import StreamsDisconnected from './account/streams-disconnected'
import MeasurementAutomatic from './account/measurement-automatic'
import ProfilesDisconnected from './account/profiles-disconnected'

export default {
	name: 'Account',

	components: {
		BoxHeader,
		BoxFooter,
		SuiNotice,
		StreamsConnected,
		ProfilesConnected,
		MeasurementManual,
		StreamsDisconnected,
		MeasurementAutomatic,
		ProfilesDisconnected,
	},

	props: {
		processing: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			error: {
				measurement: false,
			},
			valid: {
				measurement: true,
			},
		}
	},

	computed: {
		/**Eh
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
		 * Check if we can show automatic measurement ID.
		 *
		 * @since 3.4.0
		 *
		 * @return {boolean}
		 */
		showAutoMeasurement() {
			let stream = this.getOption('stream', 'google')
			let autoMeasurement = this.getOption('auto_track_ga4', 'google')
			let autoMeasurementId = this.getOption('auto_track_ga4', 'misc')

			return stream && autoMeasurement && autoMeasurementId && this.isConnected
		},

		/**
		 * Check if Google account is connected.
		 *
		 * @since 3.2.7
		 */
		showAccountNotice() {
			let account = this.getOption('account_id', 'google', 0)
			let loggedIn = this.$store.state.helpers.google.logged_in

			return loggedIn && '' !== account && 0 != account
		},

		/**
		 * Get API error if any.
		 *
		 * @since 3.4.0
		 */
		getApiErrorMessage() {
			return this.getOption('api_error', 'google', false)
		},
	},

	methods: {
		/**
		 * On tracking code validation process.
		 *
		 * @param {object} data Validation data.
		 *
		 * @since 3.3.3
		 */
		measurementValidation(data) {
			this.valid.measurement = data.valid

			if (data.valid) {
				this.error.measurement = false
			}
		},

		/**
		 * Save settings values using API.
		 *
		 * @param {string} tab Current tab.
		 *
		 * @since 3.2.4
		 */
		formSubmit(tab) {
			if (this.valid.measurement) {
				this.error = {
					measurement: false,
				}

				// Save settings.
				this.$emit('submit')
			} else {
				this.error.measurement = !this.valid.measurement
			}
		},
	},
}
</script>
