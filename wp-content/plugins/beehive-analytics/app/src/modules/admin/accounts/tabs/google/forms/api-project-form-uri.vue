<template>
	<fragment>
		<div
			role="separator"
			id="google-accounts-accordion-separator--start"
			aria-labelledby="google-accounts-accordion-separator--start"
			:aria-label="$i18n.label.start_auth_redirect"
		></div>

		<div class="beehive-accordion last-child">
			<h4 class="beehive-accordion-item--header">
				<button
					id="google-accounts-accordion-section-label--redirects"
					aria-controls="google-accounts-accordion-section--redirects"
					:aria-expanded="open ? 'true' : 'false'"
					:class="openButtonClass"
					@click="openSection"
				>
					<span
						class="sui-icon-copy sui-md"
						aria-hidden="true"
					></span>
					{{ $i18n.label.uri_redirects }}
					<span
						class="sui-sm handler"
						:class="openIconClass"
						aria-hidden="true"
					></span>
				</button>
			</h4>

			<div
				role="region"
				id="google-accounts-accordion-section--redirects"
				class="beehive-accordion-item--body"
				aria-labelledby="google-accounts-accordion-section-label--redirects"
				:hidden="!open"
				:class="bodyClass"
			>
				<p class="sui-description" style="margin-bottom: 18px;">
					{{ $i18n.desc.uri_redirects }}
				</p>

				<div class="sui-form-field">
					<label
						id="google-accounts-uri-redirects--label"
						class="sui-label"
					>
						{{ $i18n.label.uri_redirects }}
					</label>

					<div class="sui-row" style="margin-top: 5px;">
						<div class="sui-col-md-6">
							<div class="sui-with-button sui-with-button-icon">
								<input
									:value="$moduleVars.google.redirect_uri1"
									type="url"
									id="google-accounts-uri-redirects--one"
									class="sui-form-control"
									aria-labelledby="google-accounts-uri-redirects--label"
									readonly
								/>

								<button
									class="sui-button-icon beehive-copy-uri"
									:data-clipboard-text="
										$moduleVars.google.redirect_uri1
									"
									@click="copiedNotice"
								>
									<span
										aria-hidden="true"
										class="sui-icon-copy"
									></span>
									<span class="sui-screen-reader-text">
										{{ $i18n.button.copy }}
									</span>
								</button>
							</div>
						</div>

						<div class="sui-col-md-6">
							<div class="sui-with-button sui-with-button-icon">
								<input
									:value="$moduleVars.google.redirect_uri2"
									type="url"
									id="google-accounts-uri-redirects--two"
									class="sui-form-control"
									aria-labelledby="uri-redirects--label"
									readonly
								/>

								<button
									class="sui-button-icon beehive-copy-uri"
									:data-clipboard-text="
										$moduleVars.google.redirect_uri2
									"
									@click="copiedNotice"
								>
									<span
										aria-hidden="true"
										class="sui-icon-copy"
									></span>
									<span class="sui-screen-reader-text">
										{{ $i18n.button.copy }}
									</span>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div
			role="separator"
			id="google-accounts-accordion-separator--end"
			aria-labelledby="google-accounts-accordion-separator--end"
			:aria-label="$i18n.label.end_auth_redirect"
		></div>
	</fragment>
</template>

<script>
export default {
	name: 'ApiProjectFormUri',

	data() {
		return {
			open: false,
		}
	},

	mounted() {
		// Init clipboard JS.
		new ClipboardJS('.beehive-copy-uri')
	},

	computed: {
		/**
		 * Computed object to get the button class.
		 *
		 * @since 3.3.2
		 *
		 * @returns {*}
		 */
		openButtonClass() {
			return {
				open: this.open,
			}
		},

		/**
		 * Computed object to get the body class.
		 *
		 * @since 3.3.2
		 *
		 * @returns {*}
		 */
		bodyClass() {
			return {
				open: this.open,
			}
		},

		/**
		 * Computed object to get the open icon class.
		 *
		 * @since 3.3.2
		 *
		 * @returns {*}
		 */
		openIconClass() {
			return {
				'sui-icon-chevron-up': this.open,
				'sui-icon-chevron-down': !this.open,
			}
		},
	},

	methods: {
		/**
		 * Open the API redirect URI section.
		 *
		 * @since 3.3.2
		 *
		 * @returns {void}
		 */
		openSection() {
			this.open = !this.open
		},

		/**
		 * Show the URI copied message.
		 *
		 * @since 3.3.2
		 */
		copiedNotice() {
			this.$root.$emit('showTopNotice', {
				message: this.$i18n.notice.uri_copied,
			})
		},
	},
}
</script>
