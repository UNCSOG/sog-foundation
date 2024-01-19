<template>
	<fragment>
		<div
			:class="{ 'sui-form-field-error': error }"
			class="sui-form-field beehive-margin-bottom--10"
		>
			<label for="beehive-settings-measurement-id" class="sui-label">
				{{ $i18n.label.measurement_id }}
			</label>
			<!-- Measurement ID input -->
			<measurement-id
				id="beehive-settings-measurement-id"
				v-model="measurement"
				@validation="handleValidation"
			/>
			<span class="sui-description" v-if="measurementIdFromNetwork">
				{{ $i18n.desc.measurement_id_inherited }}
			</span>
		</div>
		<sui-notice v-if="error" type="error">
			<p v-html="$i18n.notice.invalid_measurement_id"></p>
		</sui-notice>
		<sui-notice type="default">
			<p v-html="$i18n.notice.measurement_id"></p>
		</sui-notice>
	</fragment>
</template>

<script>
import MeasurementId from './fields/measurement-id'
import SuiNotice from '@/components/sui/sui-notice'

export default {
	name: 'MeasurementManual',

	components: {
		SuiNotice,
		MeasurementId,
	},

	props: {
		error: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		/**
		 * Computed model object for measurement id input.
		 *
		 * @since 3.3.0
		 *
		 * @returns {string}
		 */
		measurement: {
			get() {
				return this.getOption('measurement', 'tracking', '')
			},
			set(value) {
				this.setOption('measurement', 'tracking', value)
			},
		},

		/**
		 * Check if we need to show the measurement ID inheritance description.
		 *
		 * When subsites doesn't have measurement id added, we can always inherit
		 * the measurement ID from network admin.
		 *
		 * @since 3.4.0
		 *
		 * @returns {boolean}
		 */
		measurementIdFromNetwork() {
			// Measurement IDs.
			let trackingId = this.getOption('measurement', 'tracking', '')
			let networkTrackingId = this.getOption('measurement', 'tracking', '', true)
			// Automatic Measurement IDs.
			let networkAutoTrackingId = this.getOption('auto_track_ga4', 'misc', '', true)
			// Auto tracking flag.
			let networkAutoTracking = this.getOption('auto_track_ga4', 'google', false, true)

			// If tracking is already set.
			if (trackingId || !this.isSubsite() || !this.isNetworkWide()) {
				return false
			} else {
				return (
					// If tracking ID is taken from network setup.
					networkTrackingId ||
					(networkAutoTracking && networkAutoTrackingId)
				)
			}
		},
	},

	methods: {
		/**
		 * Handle the input validation event.
		 *
		 * @param {object} data Validation data.
		 *
		 * @since 3.3.0
		 */
		handleValidation(data) {
			// Emit to parent.
			this.$emit('validation', data)
		},
	},
}
</script>
