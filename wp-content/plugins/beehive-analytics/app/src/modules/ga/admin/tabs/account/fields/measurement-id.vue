<template>
	<input
		type="text"
		class="sui-form-control"
		:id="id"
		:placeholder="$i18n.placeholder.measurement_id"
		v-model="measurement"
		@input="handleInput"
	/>
</template>

<script>
import { isValidGA4ID } from '@/helpers/utils'

export default {
	name: 'MeasurementId',

	props: {
		id: {
			type: String,
			required: true,
		},
		value: {
			type: String,
			required: true,
		},
		context: {
			type: String,
			default: '',
		},
	},

	data() {
		return {
			measurement: this.value,
		}
	},

	computed: {
		/**
		 * Validate the current measurement ID.
		 *
		 * @since 3.4.0
		 *
		 * @return {boolean}
		 */
		isValid() {
			return isValidGA4ID(this.measurement) || !this.measurement
		},
	},

	methods: {
		/**
		 * Handle the input changes in measurement input.
		 *
		 * @since 3.4.0
		 *
		 * @param event
		 */
		handleInput(event) {
			// Emit an input event.
			this.$emit('input', this.measurement)

			// Emit a validation event on input change.
			this.$emit('validation', {
				valid: this.isValid,
				context: this.context,
			})
		},
	},
}
</script>
