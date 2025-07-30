<div class="js-toolset-theme-settings-single-option-wrap toolset-theme-settings-single-option-wrap toolset-theme-settings-info-option-wrap <?php echo esc_attr( $target_css_class );?>" <?php echo $prepare_data_exclude;?> <?php echo $prepare_data_include;?>>
	<?php if ( ! empty( $element->gui->display_name ) ) { ?><label class="theme-option-label"><?php echo esc_html( $element->gui->display_name ); ?></label><?php } ?>
	<div class="theme-option-info-text-wrap">
	<?php echo $element->gui->text; ?>
	</div>
</div>
