<template>
	<fragment>
		<div class="sui-form-field beehive-margin-bottom--10">
			<label for="beehive-settings-measurement-id-auto" class="sui-label">
				{{ $i18n.label.measurement_id }}
				<span
					class="beehive-icon-tooltip sui-tooltip sui-tooltip-constrained"
					:data-tooltip="$i18n.tooltip.tracking_only"
				>
					<i class="sui-icon-info" aria-hidden="true"></i>
				</span>
				<a
					role="button"
					href="#"
					class="sui-label-link"
					@click.prevent="showManualForm"
				>
					{{ $i18n.label.use_different_measurement }}
				</a>
			</label>
			<input
				v-model="measurementId"
				type="text"
				id="beehive-settings-measurement-id-auto"
				class="sui-form-control"
				:placeholder="$i18n.placeholder.measurement_id"
				disabled
			/>
		</div>
		<sui-notice type="info">
			<p
				v-html="
					sprintf(
						$i18n.notice.automatic_measurement_enabled,
						'&lt;',
						'&gt;'
					)
				"
			></p>
		</sui-notice>
	</fragment>
</template>

<script>
import SuiNotice from '@/components/sui/sui-notice'

export default {
	name: 'MeasurementAutomatic',

	components: { SuiNotice },

	computed: {
		/**
		 * Computed model object to get auto tracking flag.
		 *
		 * @since 3.4.0
		 *
		 * @returns {boolean}
		 */
		measurementId: {
			get() {
				return this.getOption('auto_track_ga4', 'misc', '')
			},
			set(value) {
				this.setOption('auto_track_ga4', 'misc', value)
			},
		},
	},

	methods: {
		/**
		 * Show the manual measurement ID input field.
		 *
		 * @since 3.4.0
		 *
		 * @returns {void}
		 */
		showManualForm() {
			this.setOption('auto_track_ga4', 'google', false)
		},
	},
}
</script>
