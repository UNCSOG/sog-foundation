<?php

if (!defined('ABSPATH'))
    die('Security check');
if (!current_user_can(CRED_CAPABILITY)) {
    die('Access Denied');
}

?>
<form name="cred-import-user-form" enctype="multipart/form-data" action="<?php echo admin_url('admin.php'); ?>?page=toolset-export-import&tab=cred" method="post">
	<p>
		<?php _e( 'You can upload an exported .zip or .xml file from your computer:', 'wp-cred' ); ?>
	</p>
	<input type="file" class="cred-filefield" id="upload-cred-file" name="import-file" />
	<ul>
		<li>
			<label class="cred-label"><input id="checkbox-1" type="checkbox" class="cred-checkbox-invalid" name="cred-overwrite-forms" value="1" /><span class="cred-checkbox-replace"></span>
			<span><?php _e('Overwrite existing user forms', 'wp-cred'); ?></span></label>
		</li>
		<input type="hidden" name="type" value="user_forms">
		<!--<li>
			<input id="checkbox-2" type="checkbox" name="cred-delete-other-forms"  value="1" />
			<label for="checkbox-2"><?php _e('Delete forms not included in the import','wp-cred'); ?></label>
		</li>-->
		<li>
			<label class="cred-label">
				<input id="checkbox-5" type="checkbox" class="cred-checkbox-invalid" name="cred-overwrite-settings" value="1" />
				<span class="cred-checkbox-replace"></span>
				<span><?php _e('Import and Overwrite Forms Settings', 'wp-cred'); ?></span></label>
		</li>
	</ul>
	<p class="toolset-update-button-wrap">
		<input id="cred-import" class="button button-primary" type="submit" value="<?php echo esc_attr(__('Import', 'wp-cred')); ?>" name="import" />
	</p>
	<?php wp_nonce_field( 'cred-user-settings-action', 'cred-user-settings-field' ); ?>
	<?php wp_nonce_field( 'cred-user-import-nonce', 'cred-user-import-nonce' ); ?>
</form>