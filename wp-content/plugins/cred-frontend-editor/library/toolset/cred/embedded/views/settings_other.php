<?php
$settings_model = CRED_Loader::get('MODEL/Settings');
$settings = $settings_model->getSettings();
?>
<div class="js-cred-settings-wrapper">
	<?php
	do_action( 'cred_pe_general_settings', $settings );
	?>
</div>
<?php
wp_nonce_field( 'cred-other-settings', 'cred-other-settings' );
?>
