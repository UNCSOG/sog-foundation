<div class="js-toolset-theme-settings-single-option-wrap toolset-theme-settings-single-option-wrap <?php echo $target_css_class;?>" <?php echo $prepare_data_exclude;?> <?php echo $prepare_data_include;?>>
	<?php
	$switch_value = ($element->get_current_switch_value()) ? $element->get_current_switch_value() : $element->get_default_switch_value();
	?>
	<label class="theme-option-label"><?php echo $element->gui->display_name ?>:</label>

	<div class="theme-option-switch-container js-theme-option-switch-container">
		<label class="theme-option-switch-label" for="toolset-switch-<?php echo esc_attr( $element->name ); ?>-theme">
			<input type="radio" name="toolset_switch_<?php echo esc_attr( $element->name ); ?>" id="toolset-switch-<?php echo  esc_attr( $element->name ); ?>-theme"
			       class="theme-option-switch-control js-theme-option-switch-control" value="<?php echo self::TOOLSET_SWITCH_DEFAULT; ?>"
				<?php echo checked( $switch_value, self::TOOLSET_SWITCH_DEFAULT, false ); ?>
			/>
			<?php echo __( 'Use the current theme\'s setting', 'wpv-views' ); ?>
		</label>

		<label class="theme-option-switch-label theme-settings-colorpicker-label" for="toolset-switch-<?php echo esc_attr( $element->name ); ?>-custom">
			<input type="radio" name="toolset_switch_<?php echo esc_attr( $element->name ); ?>" id="toolset-switch-<?php echo  esc_attr( $element->name ); ?>-custom"
			       class="theme-option-switch-control js-theme-option-switch-control" value="<?php echo self::TOOLSET_SWITCH_CUSTOM; ?>"
				<?php echo checked( $switch_value, self::TOOLSET_SWITCH_CUSTOM, false ); ?>
			/>
			<?php echo __( 'Set a different value', 'wpv-views' ); ?>

		</label>
        <div class="theme-options-color-picker-wrap" <?php echo ( $switch_value ===  self::TOOLSET_SWITCH_DEFAULT ) ? 'style="display:none;"' : ''; ?>>
            <input type="text" class="theme-option-switch-target-input js-theme-settings-colorpicker js-theme-option-switch-target-input" data-types='<?php echo json_encode($element->type); ?>' name="<?php echo self::TOOLSET_CONTROL_PREFIX . esc_attr( $element->name ) ?>" value="<?php echo esc_attr($selected_value); ?>" id="<?php echo self::TOOLSET_CONTROL_PREFIX . esc_attr( $element->name ) ?>"  />
        </div>
	</div>

</div>
