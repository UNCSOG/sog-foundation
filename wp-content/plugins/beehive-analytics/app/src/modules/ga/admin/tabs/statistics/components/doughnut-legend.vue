<template>
	<li class="beehive-legend-item">
		<span class="beehive-legend-item-color" aria-hidden="true"></span>
		<span class="beehive-legend-item-name">
			<span>{{ title }}</span>
			<strong>{{ value }}</strong>
		</span>
	</li>
</template>

<script>
export default {
	name: 'DoughnutLegend',

	props: {
		title: String,
		value: String | Number,
	},
}
</script>
