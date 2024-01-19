<template>
	<div class="sui-sidenav-hide-lg">
		<sui-select class="sui-mobile-nav" v-model="current" :options="paths" />
	</div>
</template>

<script>
import SuiSelect from '@/components/sui/sui-select'

export default {
	name: 'MobileNav',

	components: { SuiSelect },

	props: {
		selected: {
			type: String,
			default: '/',
		},
		paths: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			current: this.selected,
		}
	},

	watch: {
		current(path) {
			if (this.$route.path !== path) {
				this.$router.push(path)
			}
		},
	},
}
</script>
