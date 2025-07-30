<div class="ddl-dialogs-container"> <!-- The create a new layout popup -->

	<div class="ddl-dialog wp-core-ui" id="ddl-container-edit">
		<div class="ddl-dialog-header">
			<h2 class="js-dialog-edit-title"><?php _e('Edit grid', 'ddl-layouts'); ?></h2>
			<h2 class="js-dialog-add-title"><?php _e('Add grid', 'ddl-layouts'); ?></h2>
			<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
		</div>

		<div class="ddl-dialog-content">

			<?php $unique_id = uniqid(); ?>
			<div class="js-popup-tabs">



				<div class="ddl-dialog-content-main ddl-popup-tab" id="js-grid-content-<?php echo $unique_id; ?>">
					<input type="hidden" name="ddl-container-edit-container-name" id="ddl-container-edit-container-name">
					<!--<div class="ddl-form">
						<p>
							<label for="ddl-container-edit-container-name"><?php _e('Grid name:', 'ddl-layouts'); ?></label>

						</p>
						<p>
							<a class="fieldset-inputs" href="<?php echo WPDLL_LEARN_ABOUT_GRIDS; ?>" target="_blank">
								<?php _e('Learn about creating and using grids', 'ddl-layouts'); ?> &raquo;
							</a>
						</p>
						
					</div>-->

				</div> <!-- .ddl-popup-tab -->
				<?php do_action('ddl-before_container_markup_controls'); ?>
				<div class="ddl-popup-tab ddl-markup-controls" id="js-grid-settings-<?php echo $unique_id; ?>">
					<?php
						$dialog_type = 'container';
						do_action('ddl-before_container_default_edit_fields');
						include 'cell_display_settings_tab.tpl.php';
						do_action('ddl-after_container_default_edit_fields');
					?>
				</div> <!-- .ddl-popup-tab -->

			</div> <!-- .js-popup-tabs -->

		</div>

		<div class="ddl-dialog-footer">
			<?php wp_nonce_field('wp_nonce_edit_css', 'wp_nonce_edit_css'); ?>
			<button class="button js-edit-dialog-close"><?php _e('Cancel','ddl-layouts') ?></button>
		  <!--  <button data-close="no" class="button button-primary js-container-dialog-edit-add-container"><?php _e('Save','ddl-layouts') ?></button>-->
			<button data-close="yes" class="button button-primary js-container-dialog-edit-save"><?php _e('Apply','ddl-layouts') ?></button>
		</div>

	</div> <!-- .ddl-dialog -->

</div>