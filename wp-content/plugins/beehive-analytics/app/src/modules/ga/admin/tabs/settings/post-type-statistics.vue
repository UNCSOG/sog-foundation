<template>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				{{ $i18n.label.statistics_posts }}
			</span>
			<span class="sui-description">
				{{ $i18n.desc.statistics_posts }}
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label class="sui-label" for="beehive-settings-post-types">
					{{ $i18n.label.post_types }}
				</label>
				<sui-select2
					id="beehive-settings-post-types"
					v-model="postTypes"
					:options="getPostTypes"
					:multiple="true"
				/>
			</div>
		</div>
	</div>
</template>

<script>
import SuiSelect2 from '@/components/sui/sui-select2'

export default {
	name: 'PostTypeStatistics',

	components: { SuiSelect2 },

	computed: {
		/**
		 * Computed object to get the post types option.
		 *
		 * @since 3.3.6
		 *
		 * @returns {[]}
		 */
		postTypes: {
			get() {
				return this.getOption('post_types', 'tracking', [])
			},
			set(value) {
				this.setOption('post_types', 'tracking', value)
			},
		},

		/**
		 * Get post types in Select2 format.
		 *
		 * @since 3.3.6
		 *
		 * @return {[]}
		 */
		getPostTypes() {
			let options = []
			let types = this.$moduleVars.post_types

			Object.keys(types).forEach((type) => {
				options.push({
					id: type,
					text: types[type],
				})
			})

			return options
		},
	},
}
</script>
