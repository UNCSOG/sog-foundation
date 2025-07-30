<?php
global $wpddlayout_theme;
$wpddlayout_theme->file_manager_export->check_theme_dir_is_writable(__('You can either make it writable by the server or download the exported layouts and save them yourself.', 'ddl-layouts'));
?>

    <div class="wrap">
        <div class="ddl-settings-wrap">

			<?php if ($wpddlayout_theme->file_manager_export->get_dir_message()): ?>
                <div class="ddl-settings">
                    <p class="toolset alert toolset-alert-error padding-10">
						<?php $wpddlayout_theme->file_manager_export->print_dir_message(); ?>
                    </p>
                </div>
			<?php endif; ?>

            <div class="ddl-settings">
                <div class="ddl-settings-header">
                    <h3><?php _e('Export layouts to theme directory', 'ddl-layouts'); ?></h3><i class="fa fa-question-circle-o ddl-export-to-theme-dir-help-icon js-ddl-export-to-theme-dir-help-icon ddl-import-export-help-icon" aria-hidden="true" data-tooltip-content="<?php esc_attr_e( __('Export the layouts to files inside the directory of the active theme. Use this option to provide your theme with default layouts.', 'ddl-layouts') ); ?>" data-tooltip-header="<?php esc_attr_e( __('Export layouts to theme directory', 'ddl-layouts') ); ?>"></i>
                </div>

                <div class="ddl-settings-content">

                    <form method="post" action="<?php echo admin_url('admin.php'); ?>?page=toolset-export-import&tab=dd_layout_import_export">
						<?php wp_nonce_field('wp_nonce_export_layouts_to_theme', 'wp_nonce_export_layouts_to_theme'); ?>
                        <p>
                            <strong><?php _e('Files will be saved in:', 'ddl-layouts'); ?></strong>
                            <code><?php echo $wpddlayout_theme->file_manager_export->get_layouts_theme_dir(); ?></code>
                        </p>

                        <p>
                            <input type="submit" class="button button-secondary" name="export_to_theme_dir"
                                   value="<?php _e('Export', 'ddl-layouts'); ?>"
							       <?php if (!$wpddlayout_theme->file_manager_export->dir_is_writable()) : ?>disabled<?php endif ?> >
                        </p>
                    </form>

					<?php
					if (isset($_POST['export_to_theme_dir'])) {
						if (WPDD_Utils::user_not_admin()) {
							die(__("You don't have permission to perform this action!", 'ddl-layouts'));
						}

						$nonce = $_POST["wp_nonce_export_layouts_to_theme"];
						if (wp_verify_nonce($nonce, 'wp_nonce_export_layouts_to_theme')) {

						$results = $wpddlayout_theme->export_layouts_to_theme();

					?>

					<?php if (sizeof($results)): ?>
                    <p>
						<?php _e('The following layouts have been exported.', 'ddl-layouts'); ?>
                    </p>

                    <ul>
						<?php foreach ($results as $result): ?>
                            <li>
								<?php if ($result['file_ok']): ?>
                                    <i class='icon-ok-sign fa fa-check-circle toolset-alert-success'></i>
								<?php else: ?>
                                    <i class='fa fa-remove fa fa-times-circle icon-remove-sign toolset-alert-error'></i>
								<?php endif; ?>
								<?php echo $result['title']; ?>
								<?php echo $result['file_name']; ?>
								<?php if (!$result['file_ok']): ?>
                                    <p class="toolset-alert-error">
										<?php _e('The file is not writable.', 'ddl-layouts'); ?>
                                    </p>
								<?php endif; ?>
                            </li>
						<?php endforeach; ?>
                        <ul>
							<?php endif ?>

							<?php

							}
							}
							?>

                </div>
                <!-- .ddl-settings-content -->
            </div>
            <!-- .ddl-settings -->

            <div class="ddl-settings">
                <div class="ddl-settings-header">
                    <h3><?php _e('Export and download layouts', 'ddl-layouts'); ?></h3><i class="fa fa-question-circle-o ddl-export-help-icon js-ddl-export-help-icon ddl-import-export-help-icon" aria-hidden="true" data-tooltip-content="<?php esc_attr_e( __('Export the layouts to a ZIP file for you to download. Use this option to move your layouts to another theme.', 'ddl-layouts') ); ?>" data-tooltip-header="<?php esc_attr_e( __('Export and download layouts', 'ddl-layouts') ); ?>"></i>
                </div>

                <div class="ddl-settings-content">

                    <form method="post" action="<?php echo admin_url('admin.php'); ?>?page=toolset-export-import&tab=dd_layout_import_export">
						<?php wp_nonce_field('wp_nonce_export_layouts', 'wp_nonce_export_layouts'); ?>
                        <p>
                            <input type="submit" class="button button-secondary" name="export_and_download"
                                   value="<?php _e('Export', 'ddl-layouts'); ?>">
                        </p>
                    </form>

                </div>
                <!-- .ddl-settings-content -->
            </div>
            <!-- .ddl-settings -->

        </div>
        <!-- .ddl-settings-wrap -->

        <div id="icon-tools" class="icon32 icon32-posts-dd_layouts"><br></div>
    </div> <!-- .wrap -->
<?php
