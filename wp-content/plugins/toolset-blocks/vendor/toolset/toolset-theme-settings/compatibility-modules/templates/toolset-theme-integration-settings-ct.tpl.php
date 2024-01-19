<div id="js-toolset-theme-settings-form-wrap" class="theme-settings-wrap wpv-advanced-setting">
	<form id="toolset_theme_settings_form">
		<span class="js-toolset-theme-settings-form">
			<?php foreach ( $collections as $group_name => $group_items ): ?>
				<?php if(count($group_items) > 0):?>
				<fieldset class="theme-settings-section" >
					<h3 class="theme-settings-section-title"><?php _e( $group_name, $this->theme_domain ); ?></h3>
					<div class="theme-settings-section-content">
						<?php foreach ( $group_items as $single_group_item ): ?>
							<?php echo $this->render_single_collection_item( $single_group_item ); ?>
						<?php endforeach; ?>
					</div>
				</fieldset>
				<?php endif;?>
			<?php endforeach; ?>
		</span>
		<input type="hidden" id="toolset-theme-display-type" name="toolset_display_type_nonce"
			   value="<?php echo wp_create_nonce( 'toolset_theme_display_type' ); ?>" />
	</form>

</div>
<p>
	<span class="update-action-wrap auto-update">
		<span class="js-wpv-message-container"></span>
		<span class="spinner ajax-loader toolset-theme-settings-spinner"></span>
	</span>
</p>
