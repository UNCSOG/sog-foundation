<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Editor_Limit_Offset::on_load();

class WPV_Editor_Limit_Offset{

	static function on_load() {
		// Register the section in the screen options of the editor pages
		add_filter( 'wpv_screen_options_editor_section_query',	array( 'WPV_Editor_Limit_Offset', 'wpv_screen_options_limit_offset' ), 40 );
		// Register the section in the editor pages
		add_action( 'wpv_action_view_editor_section_query',		array( 'WPV_Editor_Limit_Offset', 'wpv_editor_section_limit_offset' ), 40, 2 );
		// AJAX management
		add_action( 'wp_ajax_wpv_update_limit_offset',			array( 'WPV_Editor_Limit_Offset', 'wpv_update_limit_offset_callback' ) );
	}

	static function wpv_screen_options_limit_offset( $sections ) {
		$sections['limit-offset'] = array(
			'name'		=> __( 'Limit and Offset', 'wpv-views' ),
			'disabled'	=> false,
		);
		return $sections;
	}

	static function wpv_editor_section_limit_offset( $view_settings, $view_id ) {
		$view_settings = wpv_limit_offset_default_settings( $view_settings );
		$limit_options = array();
		$offset_options = array();
		for ( $index = 1; $index < 51; $index++ ) {
			$limit_options[$index] = $index;
			$offset_options[$index] = $index;
		}
		$limit_options = apply_filters( 'wpv_filter_extend_limit_options', $limit_options );
		$offset_options = apply_filters( 'wpv_filter_extend_offset_options', $offset_options );
		$hide = '';
		if (
			isset( $view_settings['sections-show-hide'] )
			&& isset( $view_settings['sections-show-hide']['limit-offset'] )
			&& 'off' == $view_settings['sections-show-hide']['limit-offset']
		) {
			$hide = ' hidden';
		}
		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'limit_and_offset' );
		?>
		<div class="wpv-setting-container wpv-settings-limit js-wpv-settings-limit-offset<?php echo $hide; ?>">
			<div class="wpv-settings-header">
				<h2>
					<?php _e( 'Limit and Offset', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
				</h2>
			</div>
			<div class="wpv-setting js-wpv-setting">

				<div class="wpv-settings-query-type-posts"<?php echo ( $view_settings['query_type'][0] != 'posts' ) ? ' style="display: none;"' : ''; ?>>

					<p>
						<span class="wpv-combo">
							<label for="wpv-settings-limit"><?php _e( 'Display ', 'wpv-views' ) ?></label>
							<select name="_wpv_settings[limit]" id="wpv-settings-limit" class="js-wpv-limit" autocomplete="off">
								<option value="-1"><?php _e('No limit', 'wpv-views'); ?></option>
								<?php
								foreach ( $limit_options as $index => $value ) {
									?>
									<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['limit'], $index, true ); ?>><?php echo $value; ?></option>
									<?php
								}
								?>
							</select>
							<span><?php _e( 'items ', 'wpv-views' ) ?></span>
						</span>
						&nbsp;&nbsp;|&nbsp;&nbsp;
						<span class="wpv-combo">
							<label for="wpv-settings-offset"><?php _e( 'Skip first', 'wpv-views' ) ?></label>
							<select name="_wpv_settings[offset]" id="wpv-settings-offset" class="js-wpv-offset" autocomplete="off">
								<option value="0"><?php _e('None', 'wpv-views'); ?></option>
								<?php
								foreach ( $offset_options as $index => $value ) {
									?>
									<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['offset'], $index, true ); ?>><?php echo $value; ?></option>
									<?php
								}
								?>
							</select>
							<span><?php _e( 'items', 'wpv-views' ) ?></span>
						<span class="wpv-combo">
					</p>
				</div>

				<div class="wpv-settings-query-type-taxonomy"<?php echo ( $view_settings['query_type'][0] != 'taxonomy' ) ? ' style="display: none;"' : ''; ?>>

					<p>
						<span class="wpv-combo">
							<label for="wpv-settings-taxonomy-limit"><?php _e( 'Display ', 'wpv-views' ) ?></label>
							<select name="_wpv_settings[taxonomy_limit]" id="wpv-settings-taxonomy-limit" class="js-wpv-taxonomy-limit" autocomplete="off">
								<option value="-1"><?php _e('No limit', 'wpv-views'); ?></option>
								<?php
								foreach ( $limit_options as $index => $value ) {
									?>
									<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['taxonomy_limit'], $index, true ); ?>><?php echo $value; ?></option>
									<?php
								}
								?>
							</select>
							<span><?php _e( 'items ', 'wpv-views' ) ?></span>
						</span>
						&nbsp;&nbsp;|&nbsp;&nbsp;
						<span class="wpv-combo">
							<label for="wpv-settings-taxonomy-offset"><?php _e( 'Skip first', 'wpv-views' ) ?></label>
							<select name="_wpv_settings[taxonomy_offset]" id="wpv-settings-taxonomy-offset" class="js-wpv-taxonomy-offset" autocomplete="off">
								<option value="0"><?php _e('None', 'wpv-views'); ?></option>
								<?php
								foreach ( $offset_options as $index => $value ) {
									?>
									<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['taxonomy_offset'], $index, true ); ?>><?php echo $value; ?></option>
									<?php
								}
								?>
							</select>
							<span><?php _e( 'items', 'wpv-views' ) ?></span>
						</span>
					</p>
				</div>
				<div class="wpv-settings-query-type-users"<?php echo ( $view_settings['query_type'][0] != 'users' ) ? ' style="display: none;"' : ''; ?>>
					 <p>
						<span class="wpv-combo">
							<label for="wpv-settings-users-limit"><?php _e( 'Display ', 'wpv-views' ) ?></label>
							<select name="_wpv_settings[users_limit]" id="wpv-settings-users-limit" class="js-wpv-users-limit" autocomplete="off">
								<option value="-1"><?php _e('No limit', 'wpv-views'); ?></option>
								<?php
								foreach ( $limit_options as $index => $value ) {
									?>
									<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['users_limit'], $index, true ); ?>><?php echo $value; ?></option>
									<?php
								}
								?>
							</select>
							<span><?php _e( 'items ', 'wpv-views' ) ?></span>
						</span>
						&nbsp;&nbsp;|&nbsp;&nbsp;
						<span class="wpv-combo">
							<label for="wpv-settings-users-offset"><?php _e( 'Skip first', 'wpv-views' ) ?></label>
							<select name="_wpv_settings[users_offset]" id="wpv-settings-users-offset" class="js-wpv-users-offset" autocomplete="off">
								<option value="0"><?php _e('None', 'wpv-views'); ?></option>
								<?php
								foreach ( $offset_options as $index => $value ) {
									?>
									<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['users_offset'], $index, true ); ?>><?php echo $value; ?></option>
									<?php
								}
								?>
							</select>
							<span><?php _e( 'items', 'wpv-views' ) ?></span>
						</span>
					</p>
				</div>
			</div>
			<span class="update-action-wrap auto-update js-wpv-update-action-wrap">
				<span class="js-wpv-message-container"></span>
				<input type="hidden" data-success="<?php echo esc_attr( __('Updated', 'wpv-views') ); ?>" data-unsaved="<?php echo esc_attr( __('Not saved', 'wpv-views') ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_limit_offset_nonce' ); ?>" class="js-wpv-limit-offset-update" />
			</span>
		</div>
	<?php
	}

	static function wpv_update_limit_offset_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_limit_offset_nonce' )
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
		$changed = false;
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		$lo_options = array(
			'limit', 'offset',
			'taxonomy_limit', 'taxonomy_offset',
			'users_limit', 'users_offset'
		);
		foreach ( $lo_options as $lo_opt) {
			if (
				isset( $_POST[$lo_opt] )
				&& (
					! isset($view_array[$lo_opt])
					|| $_POST[$lo_opt] != $view_array[$lo_opt]
				)
			) {
				if ( is_array( $_POST[$lo_opt] ) ) {
					$_POST[$lo_opt] = array_map( 'sanitize_text_field', $_POST[$lo_opt] );
				} else {
					$_POST[$lo_opt] = sanitize_text_field( $_POST[$lo_opt] );
				}
				$view_array[$lo_opt] = $_POST[$lo_opt];
				$changed = true;
			}
		}
		if ( $changed ) {
			update_post_meta($_POST["id"], '_wpv_settings', $view_array);
			do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
		}
		$data = array(
			'id' => $_POST["id"],
			'message' => __( 'Limit and Offset saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

}

