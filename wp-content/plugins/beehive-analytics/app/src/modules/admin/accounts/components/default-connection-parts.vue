<template>
	<fragment>
		<p class="sui-description" style="margin: 0 0 10px;">
			{{ $i18n.desc.connect_google }}
		</p>

		<p style="margin-top: 0;">
			<a
				type="button"
				class="sui-button sui-button-lg beehive-connect-google-btn"
				:href="$moduleVars.google.login_url"
			>
				<i class="sui-icon-google-connect" aria-hidden="true"></i>
				{{ $i18n.label.connect_google }}
			</a>
		</p>
	</fragment>
</template>

<script>

export default {
	name: 'DefaultConnectForm',

	props: {
		context: {
			type: String,
			default: 'settings',
		},
	}
}
</script>
