<template>
	<div class="beehive-tutorials-slider-box" v-show="visible">
		<tutorials-slider
			v-if="show"
			:title="$i18n.title.tutorials"
			category="11229"
			viewAll="https://wpmudev.com/blog/tutorials/tutorial-category/beehive-pro/"
			@onCloseClick="dismissSlider"
		/>
	</div>
</template>

<script>
import { ReactInVue } from 'vuera'
import { TutorialsSlider } from '@wpmudev/shared-tutorials'

export default {
	name: 'TutorialSlider',

	components: {
		'tutorials-slider': ReactInVue(TutorialsSlider),
	},

	data() {
		return {
			visible: true,
			show: false,
		}
	},

	created() {
		this.show =
			!this.$vars.whitelabel.hide_doc_link &&
			!this.getOption('hide_tutorials', 'misc')
	},

	methods: {
		/**
		 * Dismiss the slider permanently.
		 *
		 * @since 3.3.13
		 */
		dismissSlider() {
			this.visible = false
			this.setOption('hide_tutorials', 'misc', true)
			this.saveOptions()
		},
	},
}
</script>
