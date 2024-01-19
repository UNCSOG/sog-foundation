<template>
	<sui-box
		title-icon="clock"
		aria-live="polite"
		:title="$i18n.label.realtime_visitors"
		:loading="loading"
	>
		<template v-slot:headerRight>
			<!-- Stats reload button -->
			<reload-button :loading="loading" @reloaded="updateStats" />
		</template>
		<template v-slot:body>
			<p class="beehive-loading-text" v-if="loading">
				<span
					class="sui-icon-loader sui-loading"
					aria-hidden="true"
				></span>
				{{ $i18n.label.fetching_data }}
			</p>

			<!-- Auth notice -->
			<sui-notice v-if="isSubsite() && !isConnected" type="info">
				<p
					v-if="canShowSettingsMenu"
					v-html="
						sprintf(
							$i18n.notice.realtime_google_not_linked,
							$vars.urls.accounts
						)
					"
				></p>
				<p v-else v-html="$i18n.notice.google_not_linked_simple"></p>
			</sui-notice>
			<p class="sui-description" v-else-if="!isConnected">
				{{ $i18n.label.no_information }}
			</p>

			<div class="beehive-visitors" v-else>
				<div class="beehive-visitors-number">
					<strong>{{ totalSimple }}</strong>
					<span class="sui-description">Visitors now</span>
				</div>

				<div class="beehive-visitors-percentage">
					<div
						tabindex="-1"
						class="beehive-percentage-bar"
						aria-hidden="true"
					>
						<div class="beehive-progress-bar">
							<fragment
								v-for="(device, name, index) in devices"
								:key="name"
							>
								<div
									v-if="device.users > 0"
									:style="`width: ${getPercentage(
										device.users
									)}%; background-color: ${colors[index]};`"
								></div>
							</fragment>
						</div>
					</div>

					<ul class="beehive-percentage-summary">
						<li
							v-for="(device, name, index) in devices"
							:key="name"
						>
							<span
								:style="`background-color: ${colors[index]};`"
							></span>
							{{ device.device }}
							<strong>{{ getPercentage(device.users) }}%</strong>
						</li>
					</ul>
				</div>
			</div>
		</template>
	</sui-box>
</template>

<script>
import { restGetStats } from '@/helpers/api'
import SuiBox from '@/components/sui/sui-box'
import ReloadButton from './realtime/reload-button'
import SuiNotice from '@/components/sui/sui-notice'
import { hasSettingsAccess } from '@/helpers/utils'

export default {
	name: 'RealtimeVisits',

	components: {
		SuiBox,
		SuiNotice,
		ReloadButton,
	},

	data() {
		return {
			loading: false,
			total: 0,
			totalSimple: 0,
			devices: [],
			colors: ['#0582B5', '#17A8E3', '#6DD5FF'],
		}
	},

	mounted() {
		// Update stats.
		this.updateStats()
	},

	computed: {
		/**
		 * Check if Google account is connected.
		 *
		 * @since 3.3.5
		 *
		 * @return {boolean}
		 */
		isConnected() {
			return this.$store.state.helpers.google.logged_in
		},

		/**
		 * Check if realtime stats are empty from API.
		 *
		 * @since 3.3.8
		 *
		 * @return {boolean}
		 */
		isEmpty() {
			return this.total <= 0
		},

		/**
		 * Check if main menu can be shown.
		 *
		 * @since 3.3.8
		 *
		 * @return {boolean}
		 */
		canShowSettingsMenu() {
			return hasSettingsAccess()
		},
	},

	methods: {
		/**
		 * Update the realtime stats.
		 *
		 * This API call will make one request to Google API
		 * to get the realtime visit stats.
		 *
		 * @since 3.3.8
		 *
		 * @returns {void}
		 */
		async updateStats() {
			if (!this.isConnected) {
				return
			}

			// Start process.
			this.loading = true

			await restGetStats({
				path: 'stats/realtime',
				params: {
					network: this.isNetwork() ? 1 : 0,
				},
			}).then((response) => {
				if (response.success && response.data && response.data.stats) {
					this.total = response.data.stats.total
					this.devices = response.data.stats.devices
					this.totalSimple = response.data.stats.total_simple
				} else {
					this.total = 0
					this.devices = []
					this.totalSimple = 0
				}
			})

			this.loading = false
		},

		/**
		 * Get the percentage of a user group.
		 *
		 * We should round the percentage.
		 *
		 * @since 3.3.8
		 *
		 * @returns {int}
		 */
		getPercentage(users) {
			let percent = users > 0 ? (users / this.total) * 100 : 0

			return Math.round(percent)
		},
	},
}
</script>
