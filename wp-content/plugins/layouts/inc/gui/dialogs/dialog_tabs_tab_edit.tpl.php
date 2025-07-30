<div class="ddl-dialogs-container">

	<div class="ddl-dialog wp-core-ui" id="ddl-tab-edit">

		<div class="ddl-dialog-header">
			<h2 class="js-dialog-edit-title"><?php _e('Edit Tab', 'ddl-layouts'); ?></h2>
			<h2 class="js-dialog-add-title"><?php _e('Add Tab', 'ddl-layouts'); ?></h2>
			<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
		</div>

		<div class="ddl-dialog-content">

			<?php $unique_id = uniqid(); ?>
			<div class="js-popup-tabs">


				<div class="ddl-dialog-content-main ddl-popup-tab ddl-popup-tab-float" id="js-row-basic-settings-<?php echo $unique_id; ?>">

					<ul class="ddl-form js-ddl-form-tab">
						<li>
							<label for="ddl-tab-edit-tab-name"><?php _e( 'Title:', 'ddl-layouts' ); ?></label>
							<input type="text" name="ddl-tab-edit-tab-name" id="ddl-tab-edit-tab-name">
						</li>

						<li><input type="hidden" name="ddl-tab-edit-tab-name" id="ddl-tab-edit-tab-name"></li>
						<li id="ddl-tab-classes-wrap">
							<label for="ddl-tab-classes"><?php _e( 'Tab Classes:', 'ddl-layouts' ); ?></label>
							<select type="text" name="ddl-tab-classes" id="ddl-tab-classes" multiple class="js-ddl-tab-classes js-toolset-chosen-select"></select>
						</li>

						<li class="js-preset-layouts-rows" id="js-row-edit-mode">
							<label for="ddl-tab-edit-disabled"><?php _e( 'Appearance', 'ddl-layouts' ); ?>:</label>
							<div class="display-inline">
								<p>
									<input type="radio" name="ddl-tab-edit-enabled" value="enabled" checked/>
									<span class="label"><?php _e( 'Enabled', 'ddl-layouts' ); ?></span></p>
								<p>
									<input type="radio" name="ddl-tab-edit-disabled" value="disabled"/>
									<span class="label"><?php _e( 'Disabled', 'ddl-layouts' ); ?></span></p>
							</div>
							<?php //TODO: ask Dario a doc and change link here ?>
							<p>
								<a class="fieldset-inputs" href="<?php echo WPDLL_TABS_CELL_HELP; ?>" target="_blank">
									<?php _e( 'Working with tabs', 'ddl-layouts' ); ?> &raquo;
								</a>
							</p>
						</li>

					</ul>

				</div> <!-- .ddl-popup-tab -->

                <div class="clear"></div>

				<?php do_action('ddl-before_row_markup_controls'); ?>
				<div class="ddl-popup-tab ddl-markup-controls"" id="js-row-design-<?php echo $unique_id; ?>">
				<?php
				$dialog_type = 'tab';
				do_action('ddl-before_row_default_edit_fields');
				include 'cell_display_settings_tab.tpl.php';
				do_action('ddl-after_row_default_edit_fields');
				?>

			</div><!-- .ddl-popup-tab -->

		</div> <!-- .js-popup-tabs -->

	</div> <!-- .ddl-dialog-content -->

	<div class="ddl-dialog-footer">
		<?php wp_nonce_field('wp_nonce_edit_css', 'wp_nonce_edit_css'); ?>
		<button class="button js-edit-dialog-close"><?php _e('Cancel','ddl-layouts') ?></button>
	<!--	<button data-close="no" class="button button-primary js-tab-dialog-edit-save js-save-dialog-settings"><?php _e('Save','ddl-layouts') ?></button> -->
		<button data-close="yes" class="button button-primary js-tab-dialog-edit-save js-save-dialog-settings"><?php _e('Apply','ddl-layouts') ?></button>
	</div>

</div>

</div>