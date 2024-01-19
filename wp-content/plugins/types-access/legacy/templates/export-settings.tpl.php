<form name="access-export-form" action="<?php echo admin_url('admin-ajax.php'); ?>?action=access_import_export" target="_blank" method="post">
	<?php wp_nonce_field( 'access-export-form', 'access-export-form' ); ?>
	<p>
		<?php _e( 'You can export the Access settings as a .zip file.', 'wpcf-access' ); ?>
	</p>
	<p>
		<?php _e( 'That file will contain all the Access settings related to post types, taxonomies, custom groups and custom roles.', 'wpcf-access' ); ?>
	</p>
	<p class="toolset-update-button-wrap">
		<input class="button button-primary" type="submit" value="<?php echo esc_attr( __( 'Export', 'wpcf-access' ) ); ?>" name="access-export" />
	</p>
</form>