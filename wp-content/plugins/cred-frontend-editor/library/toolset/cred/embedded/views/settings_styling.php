<?php
$settings_model = CRED_Loader::get('MODEL/Settings');
$settings = $settings_model->getSettings();

if (!isset($settings['dont_load_bootstrap_cred_css'])) {
    $settings['dont_load_bootstrap_cred_css'] = 0;
}

/**
 * $settings['dont_load_cred_css'] = 1; for new installation (install)
 * $settings['dont_load_cred_css'] = 0; for old installation (update)
 */
if (!isset($settings['dont_load_cred_css'])) {
    $settings['dont_load_cred_css'] = 1;
}
?>
<div class="js-cred-settings-wrapper">
    <p>
        <label class='cred-label'>
            <input type="checkbox" autocomplete="off" class='cred-checkbox-invalid js-cred-bootstrap-styling-setting' name="cred_dont_load_bootstrap_cred_css" value="1"  <?php checked( $settings['dont_load_bootstrap_cred_css'], 1, true ); ?> />
            <span class='cred-checkbox-replace'></span>
            <span><?php _e('Do not load Forms stylesheets on front-end', 'wp-cred'); ?></span>
        </label>
    </p>
	<p>
		<label class='cred-label'>
			<input type="checkbox" autocomplete="off" class='cred-checkbox-invalid js-cred-legacy-styling-setting' name="cred_dont_load_cred_css" value="0"  <?php checked( $settings['dont_load_cred_css'], 0, true ); ?> />
			<span class='cred-checkbox-replace'></span>
			<span><?php _e('Load Forms legacy stylesheets on front-end', 'wp-cred'); ?></span>
		</label>
		<span class="description wpcf-form-description wpcf-form-description-checkbox description-checkbox">
		<?php _e( '<strong>Legacy</strong>: include the Forms styles needed for forms created before Toolset Forms 1.9', 'wp-cred' ); ?>
		</span>
	</p>
	<?php
	/* Toolset Forms 1.8.8 back compatibility */
	if ( isset( $settings['use_bootstrap'] ) )
	{ ?>
		<p>
			<label class='cred-label'>
                <input type="checkbox" autocomplete="off" class='cred-checkbox-invalid js-cred-styling-setting' name="cred_use_bootstrap" value="1" <?php if ( isset( $settings['use_bootstrap'] ) && $settings['use_bootstrap'] ) {
					echo "checked='checked'";
				} ?> />
                <span class='cred-checkbox-replace'></span>
                <span><?php _e( 'Include Bootstrap classnames in old Forms forms', 'wp-cred' ); ?></span>
            </label>
			<span class="description wpcf-form-description wpcf-form-description-checkbox description-checkbox">
			<?php _e( '<strong>Legacy</strong>: include Bootstrap classnames in forms created before Toolset Forms 1.9', 'wp-cred' ); ?>
			</span>
		</p>
	<?php } ?>
</div>
<?php wp_nonce_field( 'cred-styling-settings', 'cred-styling-settings' ); ?>