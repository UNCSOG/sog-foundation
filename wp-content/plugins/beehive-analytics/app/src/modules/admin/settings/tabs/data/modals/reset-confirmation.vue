<template>
	<div class="sui-modal sui-modal-sm">
		<div
			:id="modal"
			:aria-labelledby="`${modal}-title`"
			:aria-describedby="`${modal}-desc`"
			class="sui-modal-content sui-content-fade-in"
			aria-modal="true"
			role="dialog"
		>
			<div class="sui-box">
				<div
					class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60"
				>
					<button
						class="sui-button-icon sui-button-float--right"
						@click="closeModal"
						:id="`${modal}-close`"
					>
						<i class="sui-icon-close sui-md" aria-hidden="true"></i>
						<span class="sui-screen-reader-text">
							{{ $i18n.dialog.close }}
						</span>
					</button>

					<h3 :id="`${modal}-title`" class="sui-box-title sui-lg">
						{{ $i18n.label.reset_settings }}
					</h3>

					<p
						:id="`${modal}-desc`"
						class="sui-description"
						v-html="$i18n.desc.reset_settings_confirm"
					></p>
				</div>

				<div class="sui-box-footer sui-flatten sui-content-center">
					<button
						class="sui-button sui-button-ghost"
						@click="closeModal"
					>
						{{ $i18n.dialog.cancel }}
					</button>

					<!-- Reset button -->
					<button
						@click="resetData"
						:class="loadingClass"
						type="button"
						class="sui-button sui-button-ghost sui-button-red"
						aria-live="polite"
					>
						<span class="sui-button-text-default">
							<i class="sui-icon-undo" aria-hidden="true"></i>
							{{ $i18n.button.reset }}
						</span>
						<span class="sui-button-text-onload">
							<i
								class="sui-icon-loader sui-loading"
								aria-hidden="true"
							></i>
							{{ $i18n.button.resetting }}
						</span>
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { restGet } from '@/helpers/api'
import Modal from '@/components/mixins/modal'

export default {
	name: 'ResetConfirmation',

	mixins: [Modal],

	data() {
		return {
			processing: false,
			modal: 'beehive-data-reset-confirmation',
			openFocus: 'beehive-data-reset-confirmation-close',
			closeFocus: 'beehive-data-reset-confirmation-opener',
		}
	},

	created() {
		// Open modal on event.
		this.$root.$on('openSettingsResetModal', this.openModal)
	},

	computed: {
		/**
		 * Get the loading class for the button.
		 *
		 * @since 3.3.5
		 *
		 * @returns {*}
		 */
		loadingClass() {
			return {
				'sui-button-onload-text': this.processing,
			}
		},
	},

	methods: {
		/**
		 * Reset the plugin settings using API.
		 *
		 * Reload the current page after resetting.
		 *
		 * @since 3.3.5
		 */
		resetData() {
			this.processing = true

			restGet({
				path: 'v1/actions',
				params: {
					action: 'reset_settings',
					network: this.isNetwork() ? 1 : 0,
				},
			}).then((response) => {
				if (response.success) {
					// Show notice.
					this.$root.$emit('showTopNotice', {
						message: response.data.message,
					})

					// Reload the current page.
					setTimeout(() => {
						window.location.reload()
					}, 1000)
				} else {
					this.processing = false

					// Show notice.
					this.$root.$emit('showTopNotice', {
						type: 'error',
						message: response.data.message,
					})
				}
			})
		},
	},
}
</script>
