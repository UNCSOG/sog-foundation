<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Editor_Content::on_load();

class WPV_Editor_Content{

	static function on_load() {
		// Register the section in the screen options of the editor pages
		add_filter( 'wpv_screen_options_editor_section_layout',		array( 'WPV_Editor_Content', 'wpv_screen_options_content' ), 40 );
		add_filter( 'wpv_screen_options_wpa_editor_section_layout',	array( 'WPV_Editor_Content', 'wpv_screen_options_content' ), 40 );
		// Register the section in the editor pages
		add_action( 'wpv_action_view_editor_section_extra',			array( 'WPV_Editor_Content', 'wpv_editor_section_content' ), 10, 2 );
		add_action( 'wpv_action_view_editor_section_extra',			array( 'WPV_Editor_Content', 'wpv_editor_section_scan' ), 11, 2 );
		add_action( 'wpv_action_wpa_editor_section_extra',			array( 'WPV_Editor_Content', 'wpv_editor_section_content' ), 10, 2 );
		// AJAX management
		add_action( 'wp_ajax_wpv_update_content',					array( 'WPV_Editor_Content', 'wpv_update_content_callback' ) );
	}

	static function wpv_screen_options_content( $sections ) {
		$sections['content'] = array(
			'name'		=> __( 'Output Editor', 'wpv-views' ),
			'disabled'	=> false,
		);
		return $sections;
	}

	static function wpv_editor_section_content( $view_settings, $view_id ) {

		/* This section will be visible if
		* - this is a View (not WPA) edit page and
		* - the 'filter-extra' section is displayed (not hidden).
		*
		* Apparently default behaviour for sections is to be visible unless
		* $view_settings['sections-show-hide'][ $section_name ] == 'off'
		*
		* Note that the container div has class js-wpv-settings-filter-extra, which will cause it to be shown or hidden
		* simultaneously with the filter-extra section when user changes the according option Screen options.
		*/

		$is_section_hidden = false;

		if (
			isset( $view_settings['sections-show-hide'] )
			&& isset( $view_settings['sections-show-hide']['content'] )
			&& 'off' == $view_settings['sections-show-hide']['content'] )
		{
			$is_section_hidden = true;
		}
		$hide_class = $is_section_hidden ? 'hidden' : '';
		if (
			isset( $view_settings['view-query-mode'] )
			&& $view_settings['view-query-mode'] == 'normal'
		) {
			$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'complete_output' );
		} else if ( isset( $view_settings['query_type'][0] ) ) {
			$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'complete_output_archive' );
		}
		?>
		<div class="wpv-setting-container wpv-setting-container-horizontal wpv-settings-complete-output js-wpv-settings-content <?php echo $hide_class; ?>">

			<div class="wpv-settings-header">
				<h2>
					<?php _e( 'Output Editor', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
					<span class="js-wpv-update-button-wrap">
						<span class="js-wpv-message-container"></span>
						<input type="hidden" data-success="<?php echo esc_attr( __( 'Content updated', 'wpv-views' ) ); ?>" data-unsaved="<?php echo esc_attr( __( 'Content not saved', 'wpv-views' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpv_view_content_nonce' ) ); ?>" class="js-wpv-content-update" />
					</span>
				</h2>
			</div>

			<div class="wpv-setting">
				<div class="js-wpv-toolset-messages"></div>
				<div class="js-code-editor code-editor content-editor" data-name="complete-output-editor">
					<?php
						$full_view = get_post( $view_id );
						$content = $full_view->post_content;
					?>
					<div class="code-editor-toolbar js-code-editor-toolbar">
						<ul>
							<?php
							do_action( 'wpv_views_fields_button', 'wpv_content' );
							do_action( 'wpv_cred_forms_button', 'wpv_content' );
							?>
							<li>
								<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="<?php echo esc_attr( $view_id );?>" data-content="wpv_content">
									<i class="icon-picture fa fa-picture-o"></i>
									<span class="button-label"><?php _e('Media','wpv-views'); ?></span>
								</button>
							</li>
						</ul>
					</div>
					<textarea cols="30" rows="10" id="wpv_content" name="_wpv_settings[content]" autocomplete="off"><?php echo esc_textarea( $content ); ?></textarea>
					<?php
					wpv_formatting_help_combined_output();
					?>
				</div>
			</div>

		</div>
	<?php }

	static function wpv_editor_section_scan( $view_settings, $view_id ) {

		/* This section will be visible if
		* - this is a View (not WPA) edit page and
		*/

		if (
			! isset( $view_settings['view-query-mode'] )
			|| 'normal' != $view_settings['view-query-mode'] )
		{
			return;
		}

		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'scan_usage_view' );
		wp_nonce_field( 'work_views_listing', 'work_views_listing' );
		?>
		<div class="wpv-setting-container wpv-settings-scan-usage js-wpv-settings-scan-usage">

			<div class="wpv-settings-header">
				<h2>
					<?php _e( 'Used on', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
				</h2>
			</div>

			<div class="wpv-setting js-wpv-setting">
				<p>
					<?php
					_e( 'Here you can scan your content and check where this View is being used.', 'wpv-views' );
					?>
				</p>
				<button
					class="button button-secondary js-wpv-scan button"
					data-view-id="<?php echo esc_attr($view_id); ?>"
					data-label-edit="<?php esc_attr_e('Edit', 'wpv-views'); ?>"
					data-label-view="<?php esc_attr_e('View', 'wpv-views'); ?>"
				>
					<i class="icon-barcode fa fa-barcode"></i>
					<?php _e('Scan your content', 'wpv-views'); ?>
				</button>
				<p class="js-nothing-message hidden toolset-alert toolset-alert-info"><?php _e('This View is not used anywhere yet.', 'wpv-views'); ?></p>
			</div>

		</div>
		<?php
	}

	static function wpv_update_content_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_content_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST['id'] )
			|| ! is_numeric( $_POST['id'] )
			|| intval( $_POST['id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$content_post = get_post( $_POST['id'] );
		$content = $content_post->post_content;
		if ( $_POST["content"] != $content ) {
			$this_post = array();
			$this_post['ID'] = $_POST['id'];
			$this_post['post_content'] = $_POST['content'];
			wp_update_post( $this_post );
		}
		$content_sanitized = sanitize_post_field( 'post_content', $_POST['content'], $_POST['id'], 'db' );
		do_action( 'wpv_action_wpv_register_wpml_strings', $content_sanitized, $_POST['id'] );
		do_action( 'wpv_action_wpv_save_item', $_POST['id'] );
		$data = array(
			'id' => $_POST['id'],
			'message' => __( 'Combined Output saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

}
