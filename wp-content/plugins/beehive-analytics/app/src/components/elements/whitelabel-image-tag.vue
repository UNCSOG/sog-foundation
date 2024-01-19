<template>
	<image-tag
		class="beehive-rebranded-logo"
		:src="rebrandedImage"
		:srcset="false"
		:alt="alt"
		:full-path="true"
		v-if="isRebranded"
	/>
	<image-tag :src="src" :srcset="srcset" :alt="alt" v-else-if="canShow" />
</template>

<script>
import ImageTag from './image-tag'

export default {
	name: 'WhitelabelImageTag',

	components: { ImageTag },

	props: {
		src: {
			type: String,
			required: true,
		},
		srcset: {
			type: Boolean,
			default: true,
		},
		alt: {
			type: String,
			default: '',
		},
	},

	computed: {
		/**
		 * Check if we can show the branding image.
		 *
		 * @since 1.8.0
		 *
		 * @returns {boolean}
		 */
		canShow() {
			return !this.$vars.whitelabel.hide_branding
		},

		/**
		 * Check if custom rebranded image is set.
		 *
		 * @since 1.8.4
		 *
		 * @returns {boolean}
		 */
		isRebranded() {
			return this.$vars.whitelabel.is_rebranded
		},

		/**
		 * Get the rebranded custom image path.
		 *
		 * @since 1.8.4
		 *
		 * @returns {boolean}
		 */
		rebrandedImage() {
			return this.$vars.whitelabel.custom_image
		},
	},
}
</script>
