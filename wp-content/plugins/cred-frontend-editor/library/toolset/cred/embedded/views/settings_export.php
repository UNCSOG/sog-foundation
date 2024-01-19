<?php
$settings_model = CRED_Loader::get('MODEL/Settings');
$settings = $settings_model->getSettings();
?>
<div class="js-cred-settings-wrapper">
	<p>
		<label class='cred-label'>
			<input type="checkbox" autocomplete="off" class='cred-checkbox-invalid js-cred-export-setting' name="cred_export_settings" value="1" <?php if (isset($settings['export_settings']) && $settings['export_settings']) echo "checked='checked'"; ?> />
			<span class='cred-checkbox-replace'></span>
			<span><?php _e('Export also settings when exporting Forms', 'wp-cred'); ?></span>
		</label>
	</p>
	<p>
		<label class='cred-label'>
			<input type="checkbox" autocomplete="off" class='cred-checkbox-invalid js-cred-export-setting' name="cred_export_custom_fields" value="1" <?php if (isset($settings['export_custom_fields']) && $settings['export_custom_fields']) echo "checked='checked'"; ?> />
			<span class='cred-checkbox-replace'></span>
			<span><?php _e('Export also Custom Fields when exporting Forms', 'wp-cred'); ?></span>
		</label>
	</p>
</div>
<?php wp_nonce_field( 'cred-export-settings', 'cred-export-settings' ); ?>