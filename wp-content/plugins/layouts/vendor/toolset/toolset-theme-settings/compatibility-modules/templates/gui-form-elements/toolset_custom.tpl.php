<div class="js-toolset-theme-settings-single-option-wrap toolset-theme-settings-single-option-wrap <?php echo $target_css_class;?>" <?php echo $prepare_data_exclude;?> <?php echo $prepare_data_include;?>>
	<label class="theme-option-label" for="<?php echo esc_attr( $element->name ) ;?>"><?php echo $element->gui->display_name ?></label>
	<select data-types='<?php echo json_encode($element->type); ?>' name="<?php echo self::TOOLSET_CONTROL_PREFIX . esc_attr( $element->name ) ?>" id="<?php echo self::TOOLSET_CONTROL_PREFIX . esc_attr( $element->name ) ?>">

        <?php foreach($element->gui->values as $one_selector_item):?>
            <?php
            $selected = '';
			$selected = ($selected_value == $one_selector_item->value) ? 'selected' : '';
            ?>
			<option value="<?php echo $one_selector_item->value;?>" <?php echo $selected;?>>
                <?php echo $one_selector_item->text;?>
            </option>
		<?php endforeach;?>

	</select>
</div>
