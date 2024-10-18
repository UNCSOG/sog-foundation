<div class="js-toolset-theme-settings-single-option-wrap toolset-theme-settings-single-option-wrap <?php echo $target_css_class;?>" <?php echo $prepare_data_exclude;?> <?php echo $prepare_data_include;?>>
	<label class="theme-option-label" for="<?php echo esc_attr( $element->name ) ;?>"><?php echo $element->gui->display_name ?></label>
	<select data-types='<?php echo json_encode($element->type); ?>' name="<?php echo self::TOOLSET_CONTROL_PREFIX . esc_attr( $element->name ) ?>" id="<?php echo self::TOOLSET_CONTROL_PREFIX . esc_attr( $element->name ) ?>">

        <?php if($element->default_value === self::TOOLSET_DEFAULT ):?>
			<?php
            $selected = '';
			$selected = ($element->get_current_value() === null && $element->get_default_value() === 'toolset_use_theme_setting') ? 'selected' : '';
            ?>
            <option value="<?php echo self::TOOLSET_DEFAULT; ?>" <?php echo $selected;?>>
	            <?php echo _e('Use Theme Settings', $this->theme_domain);?>
            </option>
        <?php endif;?>

        <?php foreach($element->gui->values as $one_selector_item):?>
            <?php
            $selected = '';
			$selected = ( (string) $selected_value == (string) $one_selector_item->value) ? 'selected' : '';
            ?>
			<option value="<?php echo $one_selector_item->value;?>" <?php echo $selected;?>>
                <?php echo $one_selector_item->text;?>
            </option>
		<?php endforeach;?>

	</select>
</div>
