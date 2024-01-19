<?php
$settings_model = CRED_Loader::get('MODEL/Settings');
$settings = $settings_model->getSettings();
?>
<div class="js-cred-settings-wrapper">
	<p>
		<label class='cred-label'>
			<input type="checkbox" autocomplete="off" class='cred-checkbox-invalid js-cred-wizard-setting' name="cred_wizard" value="1" <?php if (isset($settings['wizard']) && $settings['wizard']) echo "checked='checked'"; ?> />
			<span class='cred-checkbox-replace'></span>
			<span><?php _e('Create new forms using the Forms Wizard', 'wp-cred'); ?></span>
		</label>
	</p>
</div>
<?php wp_nonce_field( 'cred-wizard-settings', 'cred-wizard-settings' ); ?>