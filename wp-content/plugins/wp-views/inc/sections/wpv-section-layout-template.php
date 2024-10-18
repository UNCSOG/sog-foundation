<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

add_action( 'wpv_action_view_editor_section_loop_output_editor_after', 'add_view_layout_templates', 10, 4 );
//add_action( 'wpv_action_wpa_editor_section_layout', 'add_view_layout_templates', 40, 4 );

function add_view_layout_templates( $view_settings, $view_layout_settings, $view_id, $user_id ) {
	$dismissed_pointers = get_user_meta( $user_id, '_wpv_dismissed_pointers', true );
	if ( ! is_array( $dismissed_pointers ) || empty( $dismissed_pointers ) ) {
		$dismissed_pointers = array();
	}
	$dismissed_dialogs = get_user_meta( $user_id, '_wpv_dismissed_dialogs', true );
	if ( ! is_array( $dismissed_dialogs ) || empty( $dismissed_dialogs ) ) {
		$dismissed_dialogs = array();
	}
	// Create nonces, we need two for backwards compatibility as Layouts is also using this.
    wp_nonce_field( 'wpv-ct-inline-edit', 'wpv-ct-inline-edit' );
	wp_nonce_field( 'wpv_inline_content_template', 'wpv_inline_content_template' );

    $templates			= array();
    $valid_templates	= array();
	$invalid_templates	= array();

	// Legacy: when creating a Slider View, we display a helper message
	// before the CT we create for its Loop.
    $first_time = get_post_meta( $view_id, '_wpv_first_time_load', true );
	delete_post_meta( $view_id, '_wpv_first_time_load' );

    if ( isset( $view_layout_settings['included_ct_ids'] ) ) {
        $templates = explode( ',', $view_layout_settings['included_ct_ids'] );
		$templates = array_map( 'esc_attr', $templates );
		$templates = array_map( 'trim', $templates );
		// is_numeric does sanitization
		$templates = array_filter( $templates, 'is_numeric' );
		$templates = array_map( 'intval', $templates );
    }
	$loop_content_template = get_post_meta( $view_id, '_view_loop_template', true );
	if ( is_numeric( $loop_content_template ) ) {
		$loop_content_template = (int) $loop_content_template;
		if ( ! in_array( $loop_content_template, $templates ) ) {
			$templates = array_merge( array( $loop_content_template ), $templates );
		}
	}
	$templates = array_unique( $templates );
	$templates = array_values( $templates );

    if ( count( $templates ) > 0 ) {
		$attached_templates = count( $templates );
		foreach ( $templates as $template_id ) {
			$template_post = get_post( $template_id, OBJECT, 'edit' );
			if (
				is_object( $template_post )
				&& $template_post->post_status  == 'publish'
				&& $template_post->post_type == 'view-template'
			) {
				$valid_templates[] = $template_id;
			} else {
				// remove Templates that might have been deleted or are missing
				$invalid_templates[] = $template_id;
			}
        }
        if ( count( $templates ) != count( $valid_templates ) ) {
			$view_layout_settings['included_ct_ids'] = implode( ',', $valid_templates );
			update_post_meta( $view_id, '_wpv_layout_settings', $view_layout_settings );
			do_action( 'wpv_action_wpv_save_item', $view_id );
        }
    }
    ?>
	<div id="attached-content-templates" class="wpv-setting wpv-settings-templates wpv-settings-layout-markup js-wpv-settings-inline-templates"<?php echo ( count( $valid_templates ) < 1 ) ? ' style="display:none;"':'' ?>>
		<h3><?php
		if (
			! isset( $view_settings['view-query-mode'] )
			|| ( 'normal' == $view_settings['view-query-mode'] )
		) {
			echo __( 'Templates for this View', 'wpv-views' );
		} else {
			echo __( 'Templates for this WordPress Archive', 'wpv-views' );
		}
		?>
		</h3>
		<?php
		if ( $first_time == 'on') {
			$purpose = $view_settings['view_purpose'];
			if ( $purpose == 'slider' ) {
				wpv_get_view_ct_slider_introduction_data();
			}
		}
		?>
		<div class="js-wpv-content-template-view-list wpv-content-template-view-list">
			<ul class="wpv-inline-content-template-listing js-wpv-inline-content-template-listing">
				<?php
				if ( count( $valid_templates ) > 0 ) {
					$opened = false;
					if ( count( $valid_templates ) == 1 ) {
						$opened = true;
					}
					foreach ( $valid_templates as $valid_ct_id ) {
						// This is cached so it is OK to do that again
						$valid_ct_post = get_post( $valid_ct_id, OBJECT, 'edit' );
						// When the user has disabled the rich editing on his profile, the original post content contains HTML entities.
						// In order to prevent this, we need to decode its content before assigning it the duplicated post content.
						$valid_ct_post->post_content = user_can_richedit()
							? $valid_ct_post->post_content
							: html_entity_decode( $valid_ct_post->post_content );
						$opened_in_loop = ( $loop_content_template == $valid_ct_id ) ? true : $opened;
						wpv_list_view_ct_item( $valid_ct_post, $valid_ct_id, $view_id, $opened_in_loop );
					}
				}
				?>
			</ul>
			<div class="js-wpv-message-container js-wpv-content-template-section-errors"></div>
		</div>
	</div>

	<!-- @todo: move this to the view-editor-section-hidden action -->
	<div id="js-wpv-inline-content-templates-dialogs" class="popup-window-container">

		<!-- Pointers -->

		<?php
		$dismissed_classname = '';
		if ( isset( $dismissed_pointers['inserted-inline-content-template'] ) ) {
			$dismissed_classname = ' js-wpv-pointer-dismissed';
		}
		?>
		<div class="js-wpv-inserted-inline-content-template-pointer<?php echo $dismissed_classname; ?>">
			<h3><?php _e( 'Content Template inserted in the layout', 'wpv-views' ); ?></h3>
			<p>
				<?php
				_e('A Content Template works like a subroutine.', 'wpv-views');
				echo WPV_MESSAGE_SPACE_CHAR;
				_e('You can edit its content in one place and use it in several places.', 'wpv-views');
				?>
			</p>
			<p>
				<label>
					<input type="checkbox" class="js-wpv-dismiss-pointer" data-pointer="inserted-inline-content-template" id="wpv-dismiss-inserted-inline-content-template-pointer" />
					<?php _e( 'Don\'t show this again', 'wpv-views' ); ?>
				</label>
			</p>
		</div>


	</div><!-- end of .popup-window-container -->
<?php
}

function wpv_list_view_ct_item( $template, $ct_id, $view_id, $opened = false ) {
	// Deprecated action, check whether it is used in Types
	do_action('views_ct_inline_editor');
	$extra_ct_attributes = apply_filters( 'wpv_filter_wpv_layout_template_extra_attributes', array(), $template, $view_id );
	$loop_template_id = get_post_meta( $view_id, WPV_View_Base::POSTMETA_LOOP_TEMPLATE_ID, true );
	$is_loop_content_template = ( $loop_template_id == $ct_id );
	// Loop Templates always get rendered open
	$opened = $is_loop_content_template ? true : $opened;
    ?>
    <li id="wpv-ct-listing-<?php echo esc_attr( $ct_id ); ?>" class="js-wpv-ct-listing js-wpv-ct-listing-show js-wpv-ct-listing-<?php echo esc_attr( $ct_id ); ?> layout-html-editor" data-id="<?php echo esc_attr( $ct_id ); ?>" data-viewid="<?php echo esc_attr( $view_id ); ?>" data-attributes="<?php echo esc_js( json_encode( $extra_ct_attributes ) ); ?>">
        <span class="wpv-inline-content-template-title js-wpv-inline-content-template-title" style="display:block;">

			<strong><?php echo esc_html( $template->post_title ); ?></strong>
			<span class="wpv-inline-content-template-action-buttons">
				<?php if ( ! $is_loop_content_template ) { ?>
				<button class="button button-secondary button-small wpv-button-remove js-wpv-ct-remove-from-view"><i class="fa fa-times" aria-hidden="true"></i> <?php _e('Remove','wpv-views'); ?></button>
				<?php } ?>
				<input type="hidden" class="js-wpv-ct-update-inline js-wpv-ct-update-inline-<?php echo esc_attr( $ct_id ); ?>" data-unsaved="<?php echo esc_attr( __( 'Not saved', 'wpv-views' ) ); ?>" data-id="<?php echo esc_attr( $ct_id ); ?>" />
				<button aria-expanded="true" class="button button-secondary button-small js-wpv-content-template-open wpv-content-template-open" data-target="<?php echo esc_attr( $ct_id ); ?>" data-viewid="<?php echo esc_attr( $view_id ); ?>">
					<i aria-hidden="true" class="js-wpv-open-close-arrow fa fa-fw fa-caret-<?php if ( $opened ) { echo 'up'; } else { echo 'down'; } ?>"> </i>
					<span class="screen-reader-text"><?php echo sprintf( __( 'Toggle Content Template panel: %s', 'wpv-views' ), $template->post_title ); ?></span>
				</button>
			</span>
			<span class="wpv-inline-content-template-user-editor-buttons js-wpv-inline-content-template-user-editor-buttons" style="display:none">
				<?php
				do_action( 'wpv_action_wpv_ct_inline_user_editor_buttons', $template );
				?>
			</span>
		</span>
        <div class="js-wpv-ct-inline-edit wpv-ct-inline-edit js-wpv-inline-editor-container-<?php echo esc_attr( $ct_id ); ?> <?php if ( ! $opened ) { echo 'hidden'; } ?>" data-template-id="<?php echo esc_attr( $ct_id ); ?>">
		<?php if ( $opened ) { ?>
			<div class="code-editor-toolbar js-code-editor-toolbar">
			   <ul class="js-wpv-v-icon js-wpv-v-icon-<?php echo esc_attr( $ct_id ); ?>">
					<?php
					do_action( 'wpv_views_fields_button', 'wpv-ct-inline-editor-' . $ct_id );

					// Action to add Toolset buttons to the inline Content Templates editor
					do_action( 'toolset_action_toolset_editor_toolbar_add_buttons', 'wpv-ct-inline-editor-' . $ct_id, 'views' );
					if ( ! defined( 'CT_INLINE' ) ) {
						define("CT_INLINE", "1");
					}
					do_action( 'wpv_cred_forms_button', 'wpv-ct-inline-editor-' . $ct_id );
					?>
					<li>
						<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="<?php echo esc_attr( $ct_id ); ?>" data-content="wpv-ct-inline-editor-<?php echo esc_attr( $ct_id ); ?>">
							<i class="icon-picture fa fa-picture-o"></i>
							<span class="button-label"><?php _e('Media','wpv-views'); ?></span>
						</button>
					</li>
			   </ul>
			</div>
			<textarea name="name" rows="10" class="js-wpv-ct-inline-editor-textarea" autocomplete="off" id="wpv-ct-inline-editor-<?php echo esc_attr( $ct_id ); ?>" data-id="<?php echo esc_attr( $ct_id ); ?>"><?php echo esc_textarea( $template->post_content ); ?></textarea>
			<?php
			$extra_css = get_post_meta( $ct_id, '_wpv_view_template_extra_css', true );
			$extra_js = get_post_meta( $ct_id, '_wpv_view_template_extra_js', true );
			wpv_add_extra_controls_css_js_after_editor_views( $ct_id, $extra_css, $extra_js );
			wpv_formatting_help_inline_content_template( $template );
			?>
		<?php } ?>
		</div>
	</li>
    <?php
}

/**
* wpv_assign_ct_to_view_callback
*
* Dialog to assign a Content Template as an inline one to a View, created by the event of clicking on the Content Template button in the Layout toolbar
*
* As we need to update the list of already assigned Content Templates along with the one of existing but not assigned, we need to do this on an AJAX call
*
* @since unknown
*/

add_action( 'wp_ajax_wpv_assign_ct_to_view', 'wpv_assign_ct_to_view_callback' );

function wpv_assign_ct_to_view_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	if (
		! isset( $_POST["wpnonce"] )
		|| (
			! wp_verify_nonce( $_POST["wpnonce"], 'wpv_inline_content_template' )
			&& ! wp_verify_nonce( $_POST["wpnonce"], 'wpv-ct-inline-edit' )
		)
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	if (
		! isset( $_POST["view_id"] )
		|| ! is_numeric( $_POST["view_id"] )
		|| intval( $_POST['view_id'] ) < 1
	) {
		$data = array(
			'type' => 'id',
			'message' => __( 'Wrong or missing ID.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}

	global $wpdb;
	$view_id = $_POST['view_id'];
	$view_settings		= get_post_meta( $view_id, '_wpv_settings', true);
	$layout_settings	= get_post_meta( $view_id, '_wpv_layout_settings', true);
	$assigned_templates = array();
	if ( isset( $layout_settings['included_ct_ids'] ) && $layout_settings['included_ct_ids'] != '' ) {
		$assigned_templates = explode( ',', $layout_settings['included_ct_ids'] );
		$assigned_templates = array_map( 'esc_attr', $assigned_templates );
		$assigned_templates = array_map( 'trim', $assigned_templates );
		// is_numeric does sanitization
		$assigned_templates = array_filter( $assigned_templates, 'is_numeric' );
		$assigned_templates = array_map( 'intval', $assigned_templates );
	}
	if (
		! isset( $view_settings['view-query-mode'] )
		|| ( 'normal' == $view_settings['view-query-mode'] )
	) {
		$query_mode = 'normal';
	} else {
		$query_mode = 'archive';
	}
	ob_start();
	?>
	<div class="wpv-dialog">
		<p>
			<?php
			_e( 'Use Content Templates as chunks of content that will be repeated in each element of the loop.', 'wpv-views' );
			?>
		</p>
		<?php
		$not_in = '';
		$not_in_array = wpv_get_loop_content_template_ids();
		$query_args = array(
			'post_type' => 'view-template',
			'orderby' => 'title',
			'order' => 'ASC',
			'posts_per_page' => '-1'
		);
		if ( count( $assigned_templates ) > 0 ) {
		?>
			<h4>
			<?php
			if ( $query_mode == 'normal' ) {
				_e( 'This View has some Content Templates already assigned', 'wpv-views' );
			} else {
				_e( 'This WordPress Archive has some Content Templates already assigned', 'wpv-views' );
			}
			?>
			</h4>
			<div style="margin-left:20px;">
				<input type="radio" name="wpv-ct-type" value="already" class="js-wpv-inline-template-type" id="js-wpv-ct-type-existing-assigned">
				<label for="js-wpv-ct-type-existing-assigned">
					<?php
					if ( $query_mode == 'normal' ) {
						_e( 'Insert a Content Template already assigned into the View', 'wpv-views' );
					} else {
						_e( 'Insert a Content Template already assigned into the WordPress Archive', 'wpv-views' );
					}
					?>
				</label>
				<div class="js-wpv-assign-ct-already" style="margin-left:20px;">
					<select class="js-wpv-inline-template-assigned-select" id="js-wpv-ct-add-id-assigned">
						<option value="0"><?php _e( 'Select a Content Template','wpv-views' ) ?>&hellip;</option>
						<?php
						foreach ( $assigned_templates as $assigned_temp ) {
						 if ( is_numeric( $assigned_temp ) ) {
							// This is cached so it is OK to load the whole post
							$template_post = get_post( $assigned_temp, OBJECT, 'edit' );
							if (
								is_object( $template_post )
								&& $template_post->post_status  == 'publish'
								&& $template_post->post_type  == 'view-template'
							) {
								$not_in_array[] =  $template_post->ID;
								echo '<option value="' . esc_attr( $template_post->ID ) . '" data-ct-name="' . esc_attr( $template_post->post_name ). '">' . esc_html( $template_post->post_title ) . '</option>';
							}
						 }
						}
						?>
					</select>
				</div>
			</div>
			<h4>
			<?php
			if ( $query_mode == 'normal' ) {
				_e( 'Assign other Content Template to the View', 'wpv-views' );
			} else {
				_e( 'Assign other Content Template to the WordPress Archive', 'wpv-views' );
			}
			?>
			</h4>
		<?php
		} else {
		?>
			<h4>
			<?php
			if ( $query_mode == 'normal' ) {
				_e( 'Assign a Content Template to the View', 'wpv-views' );
			} else {
				_e( 'Assign a Content Template to the WordPress Archive', 'wpv-views' );
			}
			?>
			</h4>
		<?php
		}
		// @todo transform this in a suggest text input
		// limit the query to just one, as we are OK with just that
		// also, it should return just IDs for performance
		if ( ! empty( $not_in_array ) ) {
			$not_in = implode( ',', $not_in_array );
			$query_args['exclude'] = $not_in;
		}
		$query = get_posts( $query_args );
		if ( count( $query ) > 0 ) {
		?>
			<div style="margin:0 0 10px 20px;">
				<input type="radio" name="wpv-ct-type" class="js-wpv-inline-template-type" value="existing" id="js-wpv-ct-type-existing">
				<label for="js-wpv-ct-type-existing">
					<?php
					if ( $query_mode == 'normal' ) {
						_e( 'Assign an existing Content template to this View','wpv-views' );
					} else {
						_e( 'Assign an existing Content template to this WordPress Archive','wpv-views' );
					}
					?>
				</label>
				<div class="js-wpv-assign-ct-existing" style="margin-left:20px;">
					<select class="js-wpv-inline-template-existing-select" id="js-wpv-ct-add-id">
						<option value="0"><?php _e( 'Select a Content Template','wpv-views' ) ?>&hellip;</option>
						<?php
						foreach( $query as $temp_post ) {
							echo '<option value="' . esc_attr( $temp_post->ID ) .'" data-ct-name="' . esc_attr( $temp_post->post_name ). '">' . esc_html( $temp_post->post_title ) .'</option>';
						}
						?>
					</select>
				</div>
			</div>
		<?php
		}
		?>
		<div style="margin:0 0 10px 20px;">
			<input type="radio" name="wpv-ct-type" class="js-wpv-inline-template-type" value="new" id="js-wpv-ct-type-new">
			<label for="js-wpv-ct-type-new">
				<?php
				if ( $query_mode == 'normal' ) {
					_e('Create a new Content Template and assign it to this View','wpv-views');
				} else {
					_e('Create a new Content Template and assign it to this WordPress Archive','wpv-views');
				}
				?>
			</label>
			<div style="margin-left:20px;" class="js-wpv-assign-ct-new">
				<input type="text" class="js-wpv-inline-template-new-name" id="js-wpv-ct-type-new-name" placeholder="<?php echo esc_attr( __( 'Type a name', 'wpv-views' ) ); ?>">
				<div class="js-wpv-add-new-ct-name-error-container"></div>
			</div>
		</div>
		<div class="js-wpv-inline-template-insert" id="js-wpv-add-to-editor-line" style="margin:10px 0 10px 20px;">
			<hr />
			<input type="checkbox" class="js-wpv-add-to-editor-check" name="wpv-ct-add-to-editor" id="js-wpv-ct-add-to-editor-btn" checked="checked">
			<label for="js-wpv-ct-add-to-editor-btn"><?php _e('Insert the Content Template shortcode to editor','wpv-views') ?></label>
		</div>
	</div>
	<?php
	$response = ob_get_clean();
	$data = array(
		'dialog_content' => $response
	);
	wp_send_json_success( $data );
}

// Load CT editor (inline - inside View editor page) TODO check nonce and, god's sake, error handling

/**
* wpv_ct_loader_inline_callback
*
* Load a Content Template in the View or WPA layout section
*
* Displays the textarea with toolbars, and optionally the formatting instructions
*
* @note used by Layouts too
*
* @since unknown
*/

add_action( 'wp_ajax_wpv_ct_loader_inline', 'wpv_ct_loader_inline_callback' );

function wpv_ct_loader_inline_callback() {
    if ( ! current_user_can( EDIT_VIEWS ) ) {
		die( "Undefined Nonce." );
	}
	if (
		! isset( $_POST["wpnonce"] )
		|| (
			! wp_verify_nonce( $_POST["wpnonce"], 'wpv_inline_content_template' )
			&& ! wp_verify_nonce( $_POST["wpnonce"], 'wpv-ct-inline-edit' )
		)	// Keep this for backwards compat and also for Layouts
	) {
		die( "Undefined Nonce." );
	}
	// @todo check why the hell this is here
    do_action('views_ct_inline_editor');
	if (
		! isset( $_POST["id"] )
		|| ! is_numeric( $_POST["id"] )
		|| intval( $_POST['id'] ) < 1
	) {
		echo 'error';
		die();
	}
    $template = get_post( $_POST['id'], OBJECT, 'edit' );
    // @todo check what the hell is that constant
	// This is for the CRED button and icon!!
	if ( ! defined( 'CT_INLINE' ) ) {
		define("CT_INLINE", "1");
	}
    if (
		is_object( $template )
		&& isset( $template->ID )
		&& isset( $template->post_type )
		&& $template->post_type == 'view-template'
	) {
        $ct_id = $template->ID;

		// When the user has disabled the rich editing on his profile, the original post content contains HTML entities.
		// In order to prevent this, we need to decode its content before assigning it the duplicated post content.
		$template->post_content = user_can_richedit()
			? $template->post_content
			: html_entity_decode( $template->post_content );

		$loaded_from = '';
		if ( isset( $_POST['include_instructions'] ) ) {
			if ( $_POST['include_instructions'] == 'inline_content_template' ) {
				$loaded_from = 'inline_content_template';
			}
			if ( $_POST['include_instructions'] == 'layouts_content_cell' ) {
				$loaded_from = 'layouts_content_cell';
			}
		}
    ?>
       	<div class="code-editor-toolbar js-code-editor-toolbar">
	       <ul class="js-wpv-v-icon js-wpv-v-icon-<?php echo esc_attr( $ct_id ); ?>">
	            <?php
				do_action( 'wpv_views_fields_button', 'wpv-ct-inline-editor-' . $ct_id );

				// Action to add Toolset buttons to the inline Content Templates editor
				do_action( 'toolset_action_toolset_editor_toolbar_add_buttons', 'wpv-ct-inline-editor-' . $ct_id, 'views' );

				do_action( 'wpv_cred_forms_button', 'wpv-ct-inline-editor-' . $ct_id );
				?>
				<li>
					<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="<?php echo esc_attr( $ct_id ); ?>" data-content="wpv-ct-inline-editor-<?php echo esc_attr( $ct_id ); ?>">
						<i class="icon-picture fa fa-picture-o"></i>
						<span class="button-label"><?php _e('Media','wpv-views'); ?></span>
					</button>
				</li>
	       </ul>
      	</div>
		<textarea name="name" rows="10" class="js-wpv-ct-inline-editor-textarea" autocomplete="off" data-id="<?php echo esc_attr( $ct_id ); ?>" id="wpv-ct-inline-editor-<?php echo esc_attr( $ct_id ); ?>"><?php echo esc_textarea( $template->post_content ); ?></textarea>
        <?php
        $extra_css = get_post_meta( $ct_id, '_wpv_view_template_extra_css', true );
        $extra_js = get_post_meta( $ct_id, '_wpv_view_template_extra_js', true );

        //outputs extra html
        wpv_add_extra_controls_css_js_after_editor_views( $ct_id, $extra_css, $extra_js );

		switch ( $loaded_from ) {
			case 'inline_content_template':
				wpv_formatting_help_inline_content_template( $template );
				break;
			case 'layouts_content_cell':
				wpv_formatting_help_layouts_content_template_cell( $template );
				break;
		}
		?>
    <?php
    } else {
       echo 'error';
    }
    die();
}

function wpv_add_extra_controls_css_js_after_editor_views( $ct_id, $extra_css, $extra_js ){
    ob_start();?>
    <div class="wpv-editor-metadata-toggle js-wpv-editor-metadata-toggle js-wpv-ct-assets-inline-editor-toggle" data-id="<?php echo esc_attr( $ct_id ); ?>" data-target="js-wpv-ct-assets-inline-css-editor-<?php echo esc_attr( $ct_id ); ?>" data-type="css">
			<span class="wpv-toggle-toggler-icon js-wpv-toggle-toggler-icon">
				<i class="fa fa-caret-down icon-large fa-lg"></i>
			</span>
        <i class="icon-pushpin fa fa-thumb-tack js-wpv-textarea-full" style="<?php echo ( empty( $extra_css ) ) ? 'display:none;' : ''; ?>"></i>
        <strong><?php _e( 'CSS editor', 'wpv-views' ); ?></strong>
    </div>
    <div id="wpv-ct-assets-inline-editor-css-<?php echo esc_attr( $ct_id ); ?>" class="wpv-ct-assets-inline-editor hidden js-wpv-ct-assets-inline-css-editor-<?php echo esc_attr( $ct_id ); ?>" data-id="<?php echo esc_attr( $ct_id ); ?>">
        <textarea name="name" class="js-wpv-ct-assets-inline-editor-textarea" autocomplete="off" id="wpv-ct-assets-inline-css-editor-<?php echo esc_attr( $ct_id ); ?>" data-id="<?php echo esc_attr( $ct_id ); ?>"><?php echo esc_textarea( $extra_css ); ?></textarea>
    </div>
    <div class="wpv-editor-metadata-toggle js-wpv-editor-metadata-toggle js-wpv-ct-assets-inline-editor-toggle" data-id="<?php echo esc_attr( $ct_id ); ?>" data-target="js-wpv-ct-assets-inline-js-editor-<?php echo esc_attr( $ct_id ); ?>" data-type="js">
			<span class="wpv-toggle-toggler-icon js-wpv-toggle-toggler-icon">
				<i class="fa fa-caret-down icon-large fa-lg"></i>
			</span>
        <i class="icon-pushpin fa fa-thumb-tack js-wpv-textarea-full" style="<?php echo ( empty( $extra_js ) ) ? 'display:none;' : ''; ?>"></i>
        <strong><?php _e( 'JS editor', 'wpv-views' ); ?></strong>
    </div>
    <div id="wpv-ct-assets-inline-editor-js-<?php echo esc_attr( $ct_id ); ?>" class="wpv-ct-assets-inline-editor hidden js-wpv-ct-assets-inline-js-editor-<?php echo esc_attr( $ct_id ); ?>" data-id="<?php echo esc_attr( $ct_id ); ?>">
        <textarea name="name" class="js-wpv-ct-assets-inline-editor-textarea" autocomplete="off" id="wpv-ct-assets-inline-js-editor-<?php echo esc_attr( $ct_id ); ?>" data-id="<?php echo esc_attr( $ct_id ); ?>"><?php echo esc_textarea( $extra_js ); ?></textarea>
    </div>
    <?php
    echo ob_get_clean();
}


/**
* wpv_ct_update_inline_callback
*
* Updates one inline Content Template in a layout section of a View or WPA
*
* @since unknown
*/

add_action( 'wp_ajax_wpv_ct_update_inline', 'wpv_ct_update_inline_callback' );

function wpv_ct_update_inline_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	if (
		! isset( $_POST["wpnonce"] )
		|| (
			! wp_verify_nonce( $_POST["wpnonce"], 'wpv_inline_content_template' )
			&& ! wp_verify_nonce( $_POST["wpnonce"], 'wpv-ct-inline-edit' )
		)
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	if (
		! isset( $_POST["ct_id"] )
		|| ! is_numeric( $_POST["ct_id"] )
		|| intval( $_POST['ct_id'] ) < 1
	) {
		$data = array(
			'type' => 'id',
			'message' => __( 'Wrong or missing ID.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
    $my_post = array();
    $my_post['ID'] = $_POST['ct_id'];
    $my_post['post_content'] = $_POST['ct_value'];
	if ( isset( $_POST['ct_title'] ) ) {
		$my_post['post_title'] = $_POST['ct_title'];
	}
	$result = wp_update_post( $my_post );

	if ( isset( $_POST['ct_css_value'] ) ) {
		$extra_css = $_POST['ct_css_value'];
		update_post_meta( $_POST['ct_id'], '_wpv_view_template_extra_css', $extra_css );
	}

	if ( isset( $_POST['ct_js_value'] ) ) {
		$extra_js = $_POST['ct_js_value'];
		update_post_meta( $_POST['ct_id'], '_wpv_view_template_extra_js', $extra_js );
	}

	do_action( 'wpv_action_wpv_register_wpml_strings', $my_post['post_content'], $my_post['ID'] );
	do_action( 'wpv_action_wpv_save_item', $_POST['ct_id'] );
	$data = array(
		'id' => $_POST["ct_id"],
		'message' => __( 'Inline Content Template saved', 'wpv-views' )
	);
	wp_send_json_success( $data );
}

/**
* wpv_remove_content_template_from_view_callback
*
* Removes a Content Template from the list of inline Templates of a View
*
* @since unknown
*/

add_action( 'wp_ajax_wpv_remove_content_template_from_view', 'wpv_remove_content_template_from_view_callback' );

function wpv_remove_content_template_from_view_callback() {
    if ( ! current_user_can( EDIT_VIEWS ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	if (
		! isset( $_POST["wpnonce"] )
		|| (
			! wp_verify_nonce( $_POST["wpnonce"], 'wpv_inline_content_template' )
			&& ! wp_verify_nonce( $_POST["wpnonce"], 'wpv-ct-inline-edit' )
		)	// Keep this for backwards compat and also for Layouts, but it has been deleted from the VIews script
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	if (
		! isset( $_POST["view_id"] )
		|| ! is_numeric( $_POST["view_id"] )
		|| intval( $_POST['view_id'] ) < 1
		|| ! isset( $_POST["template_id"] )
		|| ! is_numeric( $_POST["template_id"] )
		|| intval( $_POST['template_id'] ) < 1
	) {
		$data = array(
			'type' => 'id',
			'message' => __( 'Wrong or missing ID.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
    $view_id = $_POST['view_id'];
    $template_id = $_POST['template_id'];
    $meta = get_post_meta( $view_id, '_wpv_layout_settings', true );
    $templates = '';
    if ( isset( $meta['included_ct_ids'] ) ) {
		$reg_templates = explode( ',', $meta['included_ct_ids'] );
		$reg_templates = array_map( 'esc_attr', $reg_templates );
		$reg_templates = array_map( 'trim', $reg_templates );
		// is_numeric does sanitization
		$reg_templates = array_filter( $reg_templates, 'is_numeric' );
		$reg_templates = array_map( 'intval', $reg_templates );
		if ( in_array( $template_id, $reg_templates ) ) {
			$reg_templates = array_diff( $reg_templates, array( $template_id ) );
			$reg_templates = array_values( $reg_templates );
		}
		$templates = implode( ',', $reg_templates );
    }
    $meta['included_ct_ids'] = $templates;
	update_post_meta( $view_id, '_wpv_layout_settings', $meta );
	do_action( 'wpv_action_wpv_save_item', $view_id );
	if (
		isset( $_POST['dismiss'] )
		&& $_POST['dismiss'] == 'true'
	) {
		wpv_dismiss_dialog( 'remove-content-template-from-view' );
	}
	$data = array(
		'id' => $_POST["view_id"],
		'message' => __( 'Inline Content Template removed', 'wpv-views' )
	);
	wp_send_json_success( $data );
}
