<template>
	<div class="sui-wrap" id="beehive-wrap">
		<sui-header
			:title="$i18n.title.tutorials"
			:show-doc-link="false"
			:show-top-notice="false"
		>
			<template v-slot:right>
				<a
					role="button"
					class="sui-button"
					target="_blank"
					href="https://wpmudev.com/blog/tutorials/tutorial-category/beehive-pro/"
				>
					<span
						class="sui-icon-open-new-window"
						aria-hidden="true"
					></span>
					{{ $i18n.label.view_all }}
				</a>
			</template>
		</sui-header>
		<tutorials-list
			:title="$i18n.title.beehive_tutorials"
			category="11229"
		/>
	</div>
</template>

<script>
import SuiHeader from '@/components/sui/sui-header'
import SuiFooter from '@/components/sui/sui-footer'
import {TutorialsList} from '@wpmudev/shared-tutorials'

export default {
	name: 'App',

	components: {
		SuiHeader,
		SuiFooter,
		TutorialsList,
	},
}
</script>

<style lang="scss">
@import 'styles/main';
</style>
