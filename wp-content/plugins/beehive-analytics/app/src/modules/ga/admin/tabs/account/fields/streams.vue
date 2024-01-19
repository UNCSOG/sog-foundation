<template>
	<div class="sui-form-field">
		<label v-if="label" :for="id" class="sui-label">{{ label }}</label>
		<sui-select2
			:id="id"
			:options="getStreamsOptions"
			:placeholder="getPlaceholder"
			:disabled="isEmpty"
			:parent-element="parentElement"
			v-model="stream"
		/>
		<span v-if="showDesc" class="sui-description" v-html="$i18n.desc.account_not_here"></span>
	</div>
</template>

<script>
import SuiSelect2 from '@/components/sui/sui-select2'

export default {
	name: 'Streams',

	components: { SuiSelect2 },

	props: {
		id: {
			type: String,
			required: true,
		},
		label: {
			type: String,
			required: false,
		},
		showDesc: {
			type: Boolean,
			default: true,
		},
		parentElement: {
			type: String,
			default: '',
		},
	},

	computed: {
		/**
		 * Get the selected stream.
		 *
		 * @since 3.2.0
		 *
		 * @returns {string}
		 */
		stream: {
			get() {
				return this.getOption('stream', 'google', 0)
			},
			set(value) {
				this.setOption('stream', 'google', value)
			},
		},

		/**
		 * Get the formatted stream data for the select2 options.
		 *
		 * @since 3.4.0
		 *
		 * @return {[]}
		 */
		getStreamsOptions() {
			let options = [{
				id: 0,
				text: '- None -',
			}]

			// Loop and format property data.
			this.getStreams.forEach((item) => {
				options.push({
					id: item.name,
					text:
						item.url +
						' (' +
						item.title +
						')',
				})
			})

			return options
		},

		/**
		 * Get the placeholder text based on the stream data.
		 *
		 * If there is no streams found, show that message in placeholder.
		 *
		 * @since 3.4.0
		 *
		 * @return {string}
		 */
		getPlaceholder() {
			if (this.isEmpty) {
				return this.$i18n.placeholder.no_website
			} else {
				return this.$i18n.placeholder.select_website
			}
		},

		/**
		 * Check if the stream list is empty.
		 *
		 * @since 3.4.0
		 *
		 * @return {boolean}
		 */
		isEmpty() {
			return this.getStreams.length <= 0
		},

		/**
		 * Get streams from the Vuex state.
		 *
		 * @since 3.4.0
		 *
		 * @return {Object}
		 */
		getStreams() {
			return this.$store.state.helpers.google.streams
		},
	},
}
</script>
