<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Editor_Loop_Output_Deprecated::on_load();

class WPV_Editor_Loop_Output_Deprecated{

	static function on_load() {
		// Register the section in the editor pages
		add_action( 'wpv_action_view_editor_section_loop_output_editor_after',	array( 'WPV_Editor_Loop_Output_Deprecated', 'wpv_editor_section_loop_output_js' ), 20, 3 );
		add_action( 'wpv_action_view_editor_section_loop_output_editor_after',	array( 'WPV_Editor_Loop_Output_Deprecated', 'wpv_editor_section_loop_output_js' ), 20, 3 );
		// AJAX management
		add_action( 'wp_ajax_wpv_update_layout_extra_js',						array( 'WPV_Editor_Loop_Output_Deprecated', 'wpv_update_layout_extra_js_callback' ) );
	}

	static function wpv_screen_options_layout_extra_js( $sections ) {
		$sections['layout-settings-extra-js'] = array(
			'name'		=> __( 'Aditional Javascript files', 'wpv-views' ),
			'disabled'	=> false,
		);
		return $sections;
	}

	static function wpv_editor_section_loop_output_js( $view_settings, $view_layout_settings, $view_id ) {
		$hide = '';
		$js = isset( $view_layout_settings['additional_js'] ) ? strval( $view_layout_settings['additional_js'] ) : '';
		if (
			isset( $view_settings['sections-show-hide'] )
			&& isset( $view_settings['sections-show-hide']['layout-settings-extra-js'] )
		) {
			if ( 'off' == $view_settings['sections-show-hide']['layout-settings-extra-js'] ) {
				$hide = ' hidden';
			}
		} elseif ( '' == $js ) {
			$hide = ' hidden';
		}
		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'layout_extra_js' );
		?>
		<div class="wpv-setting wpv-settings-output-extra-js js-wpv-settings-layout-settings-extra-js<?php echo $hide; ?>">
			<h3>
				<?php echo esc_html( __( 'Additional JavaScript files', 'wpv-views' ) ); ?>
				<span class="js-wpv-update-button-wrap">
					<span class="js-wpv-message-container"></span>
					<input type="hidden" data-success="<?php echo esc_attr( __('Data saved', 'wpv-views') ); ?>" data-unsaved="<?php echo esc_attr( __('Data not saved', 'wpv-views') ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_layout_settings_extra_js_nonce' ); ?>" class="js-wpv-layout-settings-extra-js-update" />
				</span>
			</h3>
			<p>
				<label for="wpv-layout-settings-extra-js"><?php _e( 'Additional Javascript files to be loaded with this View (comma separated): ', 'wpv-views' ) ?></label>
				<input type="text" id="wpv-layout-settings-extra-js" autocomplete="off" class="js-wpv-layout-settings-extra-js" name="_wpv_layout_settings[additional_js]" value="<?php echo esc_attr( $js ); ?>" style="width:100%;" />
			</p>
		</div>
	<?php }

	static function wpv_update_layout_extra_js_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_layout_settings_extra_js_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["id"] )
			|| ! is_numeric( $_POST["id"] )
			|| intval( $_POST['id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_layout_settings', true );
		$view_array['additional_js'] = sanitize_text_field( $_POST["value"] );
		update_post_meta( $_POST["id"], '_wpv_layout_settings', $view_array );
		do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
		$data = array(
			'id' => $_POST["id"],
			'message' => __( 'Additional Javascript saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

}
