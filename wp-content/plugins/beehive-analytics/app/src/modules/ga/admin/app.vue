<template>
	<!-- Open sui-wrap -->
	<div class="sui-wrap" id="beehive-wrap">
		<sui-header
			:title="$i18n.title.google_analytics"
			:show-doc-link="!isStatsPage"
		>
			<template v-slot:left>
				<sui-select
					v-model="statisticsType"
					id="beehive-statics-type-select"
					width="175px"
					:options="$moduleVars.stats_types"
					:is-small="true"
				/>
			</template>
			<template v-slot:right>
				<!-- Button to clear the cached data -->
				<refresh-button
					:notice="!isStatsPage"
					:loading.sync="loading"
					@refreshed="$root.$emit('statsRefreshCompleted')"
				/>
				<div id="beehive-statics-period" v-if="isStatsPage">
					<sui-calendar-range
						id="beehive-stats-datepicker"
						:periods="$vars.dates.periods"
						:start-date="$vars.dates.start_date"
						:end-date="$vars.dates.end_date"
						:selected-label="$vars.dates.selected_label"
						@periodChange="changePeriods"
					/>
					<sui-checkbox
						id="beehive-stats-compare-period"
						type="small"
						:label="$i18n.label.compare_periods"
						v-model="compare"
					/>
				</div>
			</template>
		</sui-header>

		<section class="sui-row-with-sidenav">
			<div role="navigation" class="sui-sidenav">
				<div class="sui-sidenav-settings">
					<ul class="sui-vertical-tabs sui-sidenav-hide-md">
						<router-link
							class="sui-vertical-tab"
							tag="li"
							to="/statistics"
							v-if="canShowStatisticsMenu"
						>
							<a>{{ $i18n.title.statistics }}</a>
						</router-link>
						<router-link
							class="sui-vertical-tab"
							tag="li"
							to="/account"
							v-if="canShowSettingsMenu"
						>
							<a>{{ $i18n.label.account }}</a>
						</router-link>
						<router-link
							class="sui-vertical-tab"
							tag="li"
							to="/settings"
							v-if="canShowSettingsMenu"
						>
							<a>{{ $i18n.label.settings }}</a>
						</router-link>
					</ul>

					<mobile-nav :selected="$route.path" :paths="getNavs"/>
				</div>
			</div>

			<router-view
				:compare="compare"
				:processing="processing"
				:loading.sync="loading"
				@submit="saveSettings"
			/>
		</section>

		<sui-footer/>
	</div>
	<!-- Close sui-wrap -->
</template>

<script>
import SuiHeader from '@/components/sui/sui-header'
import SuiFooter from '@/components/sui/sui-footer'
import SuiSelect from '@/components/sui/sui-select'
import SuiCheckbox from '@/components/sui/sui-checkbox'
import MobileNav from '@/components/elements/mobile-nav'
import RefreshButton from '@/components/elements/refresh-button'
import SuiCalendarRange from '@/components/sui/sui-calendar-range'
import {hasStatisticsAccess, hasSettingsAccess} from '@/helpers/utils'

export default {
	name: 'App',

	components: {
		SuiHeader,
		SuiFooter,
		SuiSelect,
		MobileNav,
		SuiCheckbox,
		RefreshButton,
		SuiCalendarRange,
	},

	data() {
		return {
			compare: false,
			loading: false,
			processing: false,
		}
	},

	computed: {
		/**
		 * Check if current page is statistics page.
		 *
		 * @since 3.3.5
		 *
		 * @return {boolean}
		 */
		isStatsPage() {
			return '/statistics' === this.$route.path
		},

		/**
		 * Check if statistics menu can be shown.
		 *
		 * @since 3.3.5
		 *
		 * @return {boolean}
		 */
		canShowStatisticsMenu() {
			return hasStatisticsAccess()
		},

		/**
		 * Check if main menu can be shown.
		 *
		 * @since 3.3.5
		 *
		 * @return {boolean}
		 */
		canShowSettingsMenu() {
			return hasSettingsAccess()
		},

		/**
		 * Get the navigation items.
		 *
		 * @since 3.3.5
		 *
		 * @returns {*}
		 */
		getNavs() {
			let navs = {
				'/account': this.$i18n.label.account,
				'/settings': this.$i18n.label.settings,
			}

			// Add statistics menu if required.
			if (this.canShowStatisticsMenu) {
				navs = Object.assign(
					{'/statistics': this.$i18n.title.statistics},
					navs
				)
			}

			return navs
		},

		/**
		 * Computed object to get the selected type.
		 *
		 * @since 3.4.0
		 *
		 * @returns {string}
		 */
		statisticsType: {
			get() {
				return this.getOption('statistics_type', 'google', 'ua')
			},
			set(value) {
				this.setOption('statistics_type', 'google', value)
				// Save right away.
				this.saveOptions()
				// Emit type change event.
				this.$root.$emit('statsTypeChanged', false)
			},
		},
	},

	methods: {
		/**
		 * Save settings values using API.
		 *
		 * @since 3.2.4
		 */
		async saveSettings() {
			// Disable processing.
			this.processing = true

			// Save settings.
			let success = await this.saveOptions()

			if (success) {
				this.$root.$emit('showTopNotice', {
					message: this.$i18n.notice.changes_saved,
				})
			} else {
				this.$root.$emit('showTopNotice', {
					dismiss: true,
					type: 'error',
					message: this.$i18n.notice.changes_failed,
				})
			}

			// Disable processing.
			this.processing = false
		},

		/**
		 * Handle period change for updating stats.
		 *
		 * @since 3.3.5
		 *
		 * @param data
		 */
		changePeriods(data) {
			this.$root.$emit('statsPeriodChanged', data)
		},

		/**
		 * Handle period change for updating stats.
		 *
		 * @since 3.3.5
		 *
		 * @param data
		 */
		changeType(data) {
			this.$root.$emit('statsPeriodChanged', data)
		},
	},
}
</script>

<style lang="scss">
@import 'styles/main';
</style>
