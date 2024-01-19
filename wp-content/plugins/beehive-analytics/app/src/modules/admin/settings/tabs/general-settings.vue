<template>
	<div class="sui-box" id="beehive-settings-data-settings">
		<box-header :title="$i18n.title.general" />
		<div class="sui-box-body">
			<div class="sui-box-settings-row">
				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label">
						{{ $i18n.label.statistics_tab }}
					</span>
					<span class="sui-description">
						{{ $i18n.desc.statistics_tab }}
					</span>
				</div>
				<div class="sui-box-settings-col-2">
					<statistics-menu />
				</div>
			</div>
		</div>
		<box-footer :processing="processing" @submit="$emit('submit')" />
	</div>
</template>

<script>
import StatisticsMenu from './general/statistics-menu'
import BoxHeader from '@/components/elements/box-header'
import BoxFooter from '@/components/elements/box-footer'

export default {
	name: 'DataSettings',

	components: {
		BoxHeader,
		BoxFooter,
		StatisticsMenu,
	},

	props: {
		processing: {
			type: Boolean,
			default: false,
		},
	},
}
</script>
