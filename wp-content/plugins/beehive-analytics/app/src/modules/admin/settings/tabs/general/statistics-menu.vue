<template>
	<fragment>
		<div class="sui-form-field">
			<label for="beehive-general-statistics-menu" class="sui-toggle">
				<input
					type="checkbox"
					id="beehive-general-statistics-menu"
					aria-labelledby="beehive-general-statistics-menu-label"
					v-model="statisticsMenu"
				/>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span
					id="beehive-general-statistics-menu-label"
					class="sui-toggle-label"
				>
					{{ $i18n.label.statistics_tab_enable }}
				</span>
			</label>
		</div>
		<div class="sui-border-frame" v-if="statisticsMenu">
			<div class="sui-form-field">
				<label
					for="beehive-general-statistics-menu-title"
					id="beehive-general-statistics-menu-title-label"
					class="sui-label"
				>
					{{ $i18n.label.statistics_tab_title }}
				</label>
				<input
					id="beehive-general-statistics-menu-title"
					class="sui-form-control"
					aria-labelledby="beehive-general-statistics-menu-title-label"
					v-model="menuTitle"
					:placeholder="$i18n.label.statistics"
				/>
			</div>
		</div>
	</fragment>
</template>

<script>
export default {
	name: 'StatisticsMenu',

	computed: {
		/**
		 * Computed model object to manage settings for statistics menu.
		 *
		 * @since 3.3.6
		 *
		 * @returns {boolean}
		 */
		statisticsMenu: {
			get() {
				return this.getOption('statistics_menu', 'general', false)
			},
			set(value) {
				this.setOption('statistics_menu', 'general', value)
			},
		},

		/**
		 * Computed model object to manage settings for menu title.
		 *
		 * @since 3.3.6
		 *
		 * @returns {string}
		 */
		menuTitle: {
			get() {
				return this.getOption(
					'statistics_menu_title',
					'general',
					this.$i18n.label.statistics
				)
			},
			set(value) {
				this.setOption('statistics_menu_title', 'general', value)
			},
		},
	},
}
</script>
