<?php
$settings_model = CRED_Loader::get('MODEL/Settings');
$settings = $settings_model->getSettings();
?>
<p>
	<?php _e('Toolset Forms filters the content that is submitted by a form.', 'wp-cred'); ?>
</p>
<p>
	<input type="button" name="show_hide_allowed_tags" value="<?php echo esc_attr(__('Select allowed HTML tags', 'wp-cred')); ?>" class="button button-secondary button-large js-cred-select-allowed-tags"/>
	<?php wp_nonce_field( 'cred-manage-allowed-tags', 'cred-manage-allowed-tags' ); ?>
</p>
<div class="js-cred-allowed-tags-summary">
	<?php
	if ( ! isset( $settings['allowed_tags'] ) || empty( $settings['allowed_tags'] ) ) {
		$settings['allowed_tags'] = array();
	}
	if ( sizeof( $settings['allowed_tags'] ) > 0 ) {
		?>
		<p class="js-cred-allowed-tags-summary-text">
		<?php
		_e( 'The following HTML tags are allowed:', 'wp-cred' );
		?>
		</p>
		<ul class="toolset-taglike-list">
			<?php foreach ( $settings['allowed_tags'] as $enabled_tag => $enabled_val ): ?>
				<li><?php echo esc_html( $enabled_tag )?></li>
			<?php endforeach; ?>
		</ul>
		<?php
	} else {
		?>
		<p class="js-cred-allowed-tags-summary-text">
		<?php
		_e( 'No HTML tags have been selected.', 'wp-cred' );
		?>
		</p>
		<?php
	}
	?>
</div>