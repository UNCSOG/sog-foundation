<template>
	<label
		class="sui-tab-item"
		:class="activeClass"
		:id="`${id}-label`"
		:for="id"
	>
		<input
			type="radio"
			ref="radioInput"
			:id="id"
			:value="option"
			v-model="selected"
		/>
		{{ label }}
	</label>
</template>

<script>
export default {
	name: 'SuiTabRadio',

	props: {
		id: {
			type: String,
			required: true,
		},
		option: {
			type: String | Boolean,
			required: true,
		},
		value: {
			type: String | Boolean,
			required: true,
		},
		label: {
			type: String,
			default: true,
		},
	},

	mounted() {
		// Initialize side tabs radio.
		SUI.sideTabs(this.$refs.radioInput)
	},

	computed: {
		/**
		 * Get the active class if radio is checked.
		 *
		 * @since 1.8.1
		 *
		 * @return {*}
		 */
		activeClass() {
			return {
				active: this.selected === this.option,
			}
		},

		/**
		 * Computed model object to handle the radio selection.
		 *
		 * @since 1.8.1
		 *
		 * @returns {string}
		 */
		selected: {
			get() {
				return this.value
			},
			set() {
				// Emit change event.
				this.$emit('input', this.option)
			},
		},
	},
}
</script>
