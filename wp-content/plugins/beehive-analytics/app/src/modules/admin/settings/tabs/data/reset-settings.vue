<template>
	<!-- Reset button -->
	<button
		@click="$root.$emit('openSettingsResetModal')"
		type="button"
		id="beehive-data-reset-confirmation-opener"
		class="sui-button sui-button-ghost"
	>
		<i class="sui-icon-undo" aria-hidden="true"></i>
		{{ $i18n.button.reset }}
	</button>
</template>
