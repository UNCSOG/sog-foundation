<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Editor_Loop_Output::on_load();

class WPV_Editor_Loop_Output{

	static function on_load() {
		// Register the section in the screen options of the editor pages
		add_filter( 'wpv_screen_options_editor_section_layout',		array( 'WPV_Editor_Loop_Output', 'wpv_screen_options_loop_output' ), 20 );
		add_filter( 'wpv_screen_options_wpa_editor_section_layout',	array( 'WPV_Editor_Loop_Output', 'wpv_screen_options_loop_output' ), 20 );
		// Register the section in the editor pages
		add_action( 'wpv_action_view_editor_section_layout',		array( 'WPV_Editor_Loop_Output', 'wpv_editor_loop_output_editor' ), 20, 4 );
		add_action( 'wpv_action_wpa_editor_section_layout',			array( 'WPV_Editor_Loop_Output', 'wpv_editor_loop_output_editor' ), 20, 4 );
		// AJAX management
		add_action( 'wp_ajax_wpv_update_layout_extra',				array( 'WPV_Editor_Loop_Output', 'wpv_update_layout_extra_callback' ) );
	}

	static function wpv_screen_options_loop_output( $sections ) {
		$sections['layout-extra'] = array(
			'name'		=> __( 'Loop Editor', 'wpv-views' ),
			'disabled'	=> true,
		);
		return $sections;
	}

	static function wpv_editor_loop_output_editor( $view_settings, $view_layout_settings, $view_id, $user_id ) {
		//Get loop content template
		$loop_content_template = get_post_meta( $view_id, '_view_loop_template', true );
		if ( ! empty( $loop_content_template ) ) {
			$loop_template				= get_post( $loop_content_template );
			$loop_content_template_title = $loop_template->post_title;
			$loop_content_template_name = $loop_template->post_name;
		} else {
			$loop_content_template		= '';
			$loop_content_template_title = '';
			$loop_content_template_name	= '';
		}
		// What kind of view are we showing?
		if (
			! isset( $view_settings['view-query-mode'] )
			|| ( 'normal' == $view_settings['view-query-mode'] )
		) {
			$query_mode				= 'normal';
			$section_help_pointer	= WPV_Admin_Messages::edit_section_help_pointer( 'layout_html_css_js' );
		} else {
			// we assume 'archive' or 'layouts-loop'
			$query_mode				= 'archive';
			$section_help_pointer	= WPV_Admin_Messages::edit_section_help_pointer( 'layout_archive_html_css_js' );
		}
		$has_default_loop_output = apply_filters( 'wpv_filter_wpv_has_default_loop_output', false, $view_layout_settings, $view_id );
		?>
		<div class="wpv-setting-container wpv-setting-container-horizontal wpv-settings-layout-markup js-wpv-settings-layout-extra">

			<div class="wpv-settings-header">
				<h2>
					<?php _e( 'Loop Editor', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
							data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
							data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
					<span class="js-wpv-update-button-wrap">
						<span class="js-wpv-message-container"></span>
						<input
							type="hidden"
							data-success="<?php echo esc_attr( __( 'Data updated', 'wpv-views' ) ); ?>"
							data-unsaved="<?php echo esc_attr( __( 'Data not saved', 'wpv-views' ) ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpv_view_layout_extra_nonce' ) ); ?>"
							class="js-wpv-layout-extra-update"
						/>
					</span>
				</h2>
			</div>
			<div class="wpv-setting">
				<input type="hidden" value="<?php echo esc_attr( $loop_content_template ); ?>" id="js-loop-content-template" />
				<input type="hidden" value="<?php echo esc_attr( $loop_content_template_title ); ?>" id="js-loop-content-template-title" />
				<input type="hidden" value="<?php echo esc_attr( $loop_content_template_name ); ?>" id="js-loop-content-template-name" />
				<div class="js-error-container js-wpv-error-container js-wpv-toolset-messages"></div>
				<div class="js-code-editor code-editor layout-html-editor" data-name="layout-html-editor">
					<div class="code-editor-toolbar js-code-editor-toolbar">
						<ul class="js-wpv-layout-edit-toolbar">
							<?php
							$toolbar_action_data = array(
								'editor_id'					=> 'wpv_layout_meta_html_content',
								'view_settings'				=> $view_settings,
								'view_layout_settings'		=> $view_layout_settings,
								'view_id'					=> $view_id,
								'user_id'					=> $user_id,
								'loop_template_id'			=> $loop_content_template,
								'query_mode'				=> $query_mode,
								'has_default_loop_output'	=> $has_default_loop_output
							);
							do_action( 'wpv_action_wpv_codemirror_editor_toolbar', $toolbar_action_data );
							?>
							<?php
								do_action( 'wpv_views_fields_button', 'wpv_layout_meta_html_content' );
							?>
							<li>
								<button class="button-secondary js-code-editor-toolbar-button js-wpv-ct-assign-to-view" data-id="<?php echo esc_attr( $view_id ); ?>">
									<i class="icon-paste fa fa-clipboard"></i>
									<span class="button-label"><?php _e('Content Template','wpv-views'); ?></span>
								</button>
							</li>
							<?php
							    if( wpv_is_views_lite() ){
								?>
                                <li class=" js-wpv-disabled-pagination-tooltip">
                                    <button class="button-secondary js-code-editor-toolbar-button disabled "
                                            data-content="wpv_filter_meta_html_content">
                                        <i class="icon-pagination fa fa-wpv-custom"></i>
                                        <span class="button-label"><?php _e( 'Pagination controls', 'wpv-views' ); ?></span>
                                    </button>
                                </li>
								<?php
							    } else if ( 'normal' == $query_mode ) {
									?>
									<li class="js-wpv-editor-pagination-button-wrapper">
										<button class="button-secondary js-code-editor-toolbar-button js-wpv-pagination-popup"
											data-content="wpv_layout_meta_html_content">
											<i class="icon-pagination fa fa-wpv-custom"></i>
											<span class="button-label"><?php _e('Pagination controls','wpv-views'); ?></span>
											<i class="icon-bookmark fa fa-bookmark flow-warning js-wpv-pagination-control-button-incomplete" style="display:none"></i>
										</button>
									</li>
									<?php
								} else if( 'archive' == $query_mode ) {
									?>
									<li class="js-wpv-archive-editor-pagination-button-wrapper">
										<button class="button-secondary js-code-editor-toolbar-button js-wpv-archive-pagination-popup"
												data-content="wpv_layout_meta_html_content">
											<i class="icon-pagination fa fa-wpv-custom"></i>
											<span class="button-label"><?php _e( 'Pagination controls', 'wpv-views' ); ?></span>
											<i class="icon-bookmark fa fa-bookmark flow-warning js-wpv-pagination-control-button-incomplete" style="display:none"></i>
										</button>
									</li>
									<?php
								}

								// Action to add Toolset buttons to the Loop editor
								do_action( 'toolset_action_toolset_editor_toolbar_add_buttons', 'wpv_layout_meta_html_content', 'views' );

								do_action( 'wpv_cred_forms_button', 'wpv_layout_meta_html_content' );

							?>
							<li>
								<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager"
										data-id="<?php echo esc_attr( $view_id ); ?>"
										data-content="wpv_layout_meta_html_content">
									<i class="icon-picture fa fa-picture-o"></i>
									<span class="button-label"><?php _e('Media','wpv-views'); ?></span>
								</button>
							</li>
						</ul>
					</div>

					<textarea cols="30" rows="10" id="wpv_layout_meta_html_content" autocomplete="off" name="_wpv_layout_settings[layout_meta_html]"><?php echo ( isset( $view_layout_settings['layout_meta_html'] ) ) ? esc_textarea( $view_layout_settings['layout_meta_html'] ) : ''; ?></textarea>
					<?php
					$layout_extra_css	= isset( $view_settings['layout_meta_html_css'] ) ? $view_settings['layout_meta_html_css'] : '';
					$layout_extra_js	= isset( $view_settings['layout_meta_html_js'] ) ? $view_settings['layout_meta_html_js'] : '';
					?>
					<div class="wpv-editor-metadata-toggle js-wpv-editor-metadata-toggle js-wpv-assets-editor-toggle" data-instance="layout-css-editor" data-target="js-wpv-assets-layout-css-editor" data-type="css">
						<span class="wpv-toggle-toggler-icon js-wpv-toggle-toggler-icon">
							<i class="fa fa-caret-down icon-large fa-lg"></i>
						</span>
						<i class="icon-pushpin fa fa-thumb-tack js-wpv-textarea-full" style="<?php echo ( empty( $layout_extra_css ) ) ? 'display:none;' : ''; ?>"></i>
						<strong><?php _e( 'CSS editor', 'wpv-views' ); ?></strong>
					</div>
					<div id="wpv-assets-layout-css-editor" class="wpv-assets-editor hidden js-wpv-assets-layout-css-editor">
						<textarea cols="30" rows="10" id="wpv_layout_meta_html_css" autocomplete="off" name="_wpv_settings[layout_meta_html_css]"><?php echo esc_textarea( $layout_extra_css ); ?></textarea>
					</div>

					<div class="wpv-editor-metadata-toggle js-wpv-editor-metadata-toggle js-wpv-assets-editor-toggle" data-instance="layout-js-editor" data-target="js-wpv-assets-layout-js-editor" data-type="js">
						<span class="wpv-toggle-toggler-icon js-wpv-toggle-toggler-icon">
							<i class="fa fa-caret-down icon-large fa-lg"></i>
						</span>
						<i class="icon-pushpin fa fa-thumb-tack js-wpv-textarea-full" style="<?php echo ( empty( $layout_extra_js ) ) ? 'display:none;' : ''; ?>"></i>
						<strong><?php _e( 'JS editor', 'wpv-views' ); ?></strong>
					</div>
					<div id="wpv-assets-layout-js-editor" class="wpv-assets-editor hidden js-wpv-assets-layout-js-editor">
						<textarea cols="30" rows="10" id="wpv_layout_meta_html_js" autocomplete="off" name="_wpv_settings[layout_meta_html_js]"><?php echo esc_textarea( $layout_extra_js ); ?></textarea>
					</div>
					<?php
						wpv_formatting_help_layout();
					?>
				</div>
			</div>
			<?php
			// Add the inline Content Templates inside this main section
			do_action( 'wpv_action_view_editor_section_loop_output_editor_after', $view_settings, $view_layout_settings, $view_id, $user_id );
			?>
		</div>
		<?php
	}

	static function wpv_update_layout_extra_callback() {

		// Authentication
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_layout_extra_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		$view_id = (int) wpv_getpost( 'id', 0 );

		// This will give us a View, a WPA or null.
		$view = WPV_View_Base::get_instance( $view_id );

		if (
			$view_id < 1
			|| ( null == $view )
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		try {

			// We're updating multiple properties at once.
			$view->defer_after_update_actions();

			// Actually we're changing only View settings and loop settings here.
			// If any of those changes fails, the database will not be updated.
			$view->begin_modifying_view_settings();
			$view->begin_modifying_loop_settings();

			$view->css	= wpv_getpost( 'layout_css_val' );
			$view->js	= wpv_getpost( 'layout_js_val' );

			$view->loop_meta_html = wpv_getpost( 'layout_val' );

			// Save the wizard settings
			if ( isset( $_POST['include_wizard_data'] ) ) {

				$view->loop_style				= wpv_getpost( 'style' );
				$view->loop_table_column_count	= wpv_getpost( 'table_cols' );
				$view->loop_bs_column_count		= wpv_getpost( 'bootstrap_grid_cols' );
				$view->loop_bs_grid_container	= wpv_getpost( 'bootstrap_grid_container' );
				$view->loop_row_class			= wpv_getpost( 'bootstrap_grid_row_class' );
				$view->loop_bs_individual		= wpv_getpost( 'bootstrap_grid_individual' );
				$view->loop_include_field_names	= wpv_getpost( 'include_field_names' );
				$view->loop_fields				= wpv_getpost( 'fields' ); // @todo sanitize this
				$view->loop_real_fields			= wpv_getpost( 'real_fields' ); // @todo sanitize this
				$view->list_separator = wpv_getpost( 'list_separator' ); // This doesn't need sanitization as it will be sanitized as part of the editor content when the time comes.

				// Remove unused Content Template
				$ct_to_delete = (int) wpv_getpost( 'delete_view_loop_template', 0 );
				if ( $ct_to_delete > 0 ) {
					$view->delete_unused_loop_template( $ct_to_delete );
				}

			}

			// Now store changes.
			$view->finish_modifying_view_settings();
			$view->finish_modifying_loop_settings();
			$view->resume_after_update_actions();


		} catch ( WPV_RuntimeExceptionWithMessage $e ) {

			// Validation errors go here.
			wp_send_json_error( array( 'type' => 'update', 'message' => $e->getUserMessage() ) );

		} catch ( Exception $e ) {

			wp_send_json_error( array( 'type' => 'update', 'message' => __( 'An unexpected error ocurred.', 'wpv-views' ) ) );
		}

		// Success!
		$data = array(
			'id' => $view_id,
			'message' => __( 'Loop saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

}
