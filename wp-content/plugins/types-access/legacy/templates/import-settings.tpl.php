<form name="access-import-form" enctype="multipart/form-data" action="<?php echo admin_url('admin.php'); ?>?page=toolset-export-import&tab=access" method="post">
	<?php wp_nonce_field( 'access-import-form', 'access-import-form' ); ?>
	<p>
		<?php _e( 'You can upload an exported .zip or .xml file from your computer:', 'wpcf-access' ); ?>
	</p>
	<input type="file" name="access-import-file" class="js-wpcf-access-import-file" />
	<ul>
		<li>
			<label><input type="checkbox" name="access-overwrite-existing-settings" value="1" />
			<span><?php _e( 'Overwrite existing Access settings', 'wpcf-access' ); ?></span></label>        
		</li>
		<li>
			<label><input type="checkbox" name="access-remove-not-included-settings" value="1" />
			<span><?php _e( 'Delete Access settings not included in the imported file', 'wpcf-access' ); ?></span></label>
		</li>
	</ul>
	<p class="toolset-update-button-wrap">
		<input class="button button-primary js-wpcf-access-import-button" data-error="<?php echo esc_attr( __( 'Please add file.', 'wpcf-access' ) ); ?>" type="submit" value="<?php echo esc_attr( __( 'Import', 'wpcf-access' ) ); ?>" name="access-import" />
	</p>
</form>