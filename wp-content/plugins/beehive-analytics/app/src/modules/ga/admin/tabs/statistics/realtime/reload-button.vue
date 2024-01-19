<template>
	<button
		type="button"
		class="sui-button sui-button-ghost"
		aria-live="polite"
		:class="loadingClass"
		:disabled="disabled"
		@click="reloadData"
	>
		<span class="sui-button-text-default">
			<i class="sui-icon-refresh" aria-hidden="true"></i>
			{{ $i18n.button.reload }}
		</span>
		<span class="sui-button-text-onload">
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
			{{ $i18n.button.reloading }}
		</span>
	</button>
</template>

<script>
export default {
	name: 'ReloadButton',

	props: {
		loading: {
			type: Boolean,
			default: false,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		/**
		 * Get button loading class if processing.
		 *
		 * @since 3.3.8
		 *
		 * @returns {*}
		 */
		loadingClass() {
			return {
				'sui-button-onload-text': this.loading,
			}
		},
	},

	methods: {
		/**
		 * Process the reload button click.
		 *
		 * @since 3.3.8
		 *
		 * @returns {void}
		 */
		reloadData() {
			// Emit reload event.
			this.$emit('reloaded')
		},
	},
}
</script>
