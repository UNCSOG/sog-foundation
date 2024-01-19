<template>
	<tr>
		<th class="content-title">{{ label }}</th>
		<td class="content-center">
			<span :class="tagClass">
				{{ tagLabel }}
			</span>
		</td>
		<td class="content-right">
			<a
				role="button"
				class="sui-button-icon"
				:href="$vars.urls.ga_account"
			>
				<span
					class="sui-icon-widget-settings-config"
					aria-hidden="true"
				></span>
				<span class="sui-screen-reader-text">
					{{ $i18n.button.configure }}
				</span>
			</a>
		</td>
	</tr>
</template>

<script>
export default {
	name: 'ConfigRow',

	props: {
		label: String,
		status: String,
	},

	computed: {
		/**
		 * Get dynamic classes for tag.
		 *
		 * @since 3.4.0
		 *
		 * @returns {*}
		 */
		tagClass() {
			return {
				'sui-tag': true,
				'sui-tag-sm': true,
				'sui-tag-red': 'error' === this.status,
				'sui-tag-green': 'active' === this.status
			}
		},

		/**
		 * Get dynamic label for tag.
		 *
		 * @since 3.4.0
		 *
		 * @returns {string}
		 */
		tagLabel() {
			if ('error' === this.status) {
				return this.$i18n.label.item_error
			} else {
				return 'active' === this.status ? this.$i18n.label.item_active : this.$i18n.label.item_inactive
			}
		}
	}
}
</script>
