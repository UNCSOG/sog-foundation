<div class="ddl-dialogs-container">

	<div class="ddl-dialog wp-core-ui" id="ddl-panel-edit">

		<div class="ddl-dialog-header">
			<h2 class="js-dialog-edit-title"><?php _e('Edit accordion panel', 'ddl-layouts'); ?></h2>
			<h2 class="js-dialog-add-title"><?php _e('Add accordion panel', 'ddl-layouts'); ?></h2>
			<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
		</div>

		<div class="ddl-dialog-content">

			<?php $unique_id = uniqid(); ?>
			<div class="js-popup-panels">


				<div class="ddl-dialog-content-main ddl-popup-panel ddl-popup-panel-float" id="js-row-basic-settings-<?php echo $unique_id; ?>">

					<ul class="ddl-form js-ddl-form-panel ddl-form-panel">
						<li class="pad-top-12">
							<label for="ddl-panel-edit-panel-name"><?php _e( 'Box name:', 'ddl-layouts' ); ?></label>
							<input type="text" name="ddl-panel-edit-panel-name" id="ddl-panel-edit-panel-name">
						</li>
						<li class="pad-top-12">
							<label for="ddl-panel-classes"><?php _e( 'Panel classes:', 'ddl-layouts' ); ?></label>
							<select type="text" name="ddl-panel-classes" id="ddl-panel-classes" multiple class="js-ddl-panel-classes js-toolset-chosen-select"></select>
						</li>
						<li><p>
								<a class="fieldset-inputs" href="<?php echo WPDLL_ACCORDION_CELL_HELP; ?>"
								   target="_blank">
									<?php _e( 'Working with accordion', 'ddl-layouts' ); ?> &raquo;
								</a>
							</p>
						</li>
					</ul>

				</div> <!-- .ddl-popup-panel -->

                <div class="clear"></div>

				<?php do_action('ddl-before_row_markup_controls'); ?>
				<div class="ddl-popup-panel ddl-markup-controls"" id="js-row-design-<?php echo $unique_id; ?>">
				<?php
				$dialog_type = 'panel';
				do_action('ddl-before_row_default_edit_fields');
				include 'cell_display_settings_tab.tpl.php';
				do_action('ddl-after_row_default_edit_fields');
				?>

			</div><!-- .ddl-popup-panel -->

		</div> <!-- .js-popup-panels -->

	</div> <!-- .ddl-dialog-content -->

	<div class="ddl-dialog-footer">
		<?php wp_nonce_field('wp_nonce_edit_css', 'wp_nonce_edit_css'); ?>
		<button class="button js-edit-dialog-close"><?php _e('Cancel','ddl-layouts') ?></button>
	<!--	<button data-close="no" class="button button-primary js-panel-dialog-edit-save js-save-dialog-settings"><?php _e('Save','ddl-layouts') ?></button> -->
		<button data-close="yes" class="button button-primary js-panel-dialog-edit-save js-save-dialog-settings"><?php _e('Apply','ddl-layouts') ?></button>
	</div>

</div>

</div>