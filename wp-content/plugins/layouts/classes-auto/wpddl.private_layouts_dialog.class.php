<?php

class WPDDL_PrivateLayoutsDialog extends Toolset_DialogBoxes{

	function __construct( $screens ){
		parent::__construct( $screens );
	}

	public function template(){
		ob_start();
		?>
		<script type="text/html" id="js-ddl-private_layouts_switcher">
			<div id="js-dialog-dialog-container">
				<div class="ddl-dialog-content" id="js-dialog-content-dialog">

					<span class="dialog-alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>
					<span class="dialog-content">
                            <b><?php _e('After disabling Layouts page builder for this page, what do you want to show inside editor?', 'ddl-layouts'); ?></b><br>
                            <ul>
                                <li>
                                    <input type="radio" name="ddl-pl-what_to_edit" id="ddl-pl-edit-original" value="original_content"><label for="ddl-pl-edit-original"><?php _e('The last version before using Layouts','ddl-layouts');?> <span><i class="fa fa-question-circle js-pl-tooltip" title="<?php _e('We saved the content of the WordPress editor before you started designing it with Layouts. This will restore that value, so you can continue editing it, instead of what you designed with Layouts.','dd-layouts');?>"></i></span></label>
                                </li>
                                <li>
                                    <input type="radio" name="ddl-pl-what_to_edit" id="dd-pl-edit-layout-output" value="layout_output"><label for="dd-pl-edit-layout-output"><?php _e('The design created by Layouts','ddl-layouts');?> <span><i class="fa fa-question-circle js-pl-tooltip" title="<?php _e('This will render the design that you did with Layouts into the WordPress editor, including all styling and positioning, so you can continue editing it manually.','dd-layouts');?>"></i></span></label>
                                </li>
                            </ul>
                    </span>
				</div>
			</div>
		</script>

		<script type="text/html" id="js-ddl-unsaved_data_dialog">
			<div id="js-dialog-dialog-container">
				<div class="ddl-dialog-content" id="js-dialog-content-dialog">
					<?php _e('Your recent edits to this post are not yet saved. If you switch to designing with Layouts now, you will lose these edits. Please save your post as a draft or publish it.', 'ddl-layouts'); ?>
				</div>
			</div>
		</script>

		<?php
		echo ob_get_clean();
	}
}

