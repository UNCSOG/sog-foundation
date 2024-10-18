<?php
/**
 * General file for all AJAX calls
 *
 * All AJAX calls used in the backend must be set here.
 */

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;
use \OTGS\Toolset\Views\Services\ContentTemplateService;

/* ************************************************************************* *\
        Views & WPA edit sceen
\* ************************************************************************* */

/*
* Views listing screen
*/

/**
 * Render a popup to confirm bulk Views trashing or deleting.
 *
 * This is called by deleteViewsConfirmation() in views_listing_page.js. The Popup is identified by class
 * "js-bulk-delete-views-dialog". It also contains a table with Views to be deleted and buttons to scan for their usage.
 *
 * Delete button (js-bulk-remove-view-permanent) contains data attributes "view-ids" with comma-separated list of
 * View IDs and "nonce" with a wpv_bulk_remove_view_permanent_nonce.
 *
 * Following POST variables are expected:
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 * - ids: An array of View IDs to be trashed/deleted
 * - view_action: Action to perform: 'delete' or 'trash'.
 *
 * @since 1.7
 */
add_action( 'wp_ajax_wpv_view_bulk_trashdel_render_popup', 'wpv_view_bulk_trashdel_render_popup_callback' );

function wpv_view_bulk_trashdel_render_popup_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	if (
		! isset( $_POST["wpnonce"] )
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_listing_actions_nonce' )
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}

	$post_ids = wpv_getpost( 'ids', array() );
	if ( is_string( $_POST['ids'] ) ) {
		$post_ids = array( $_POST['ids'] );
	}
	// We only get IDs and titles
	$post_ids = array_map( 'esc_attr', $post_ids );
	$post_ids = array_map( 'trim', $post_ids );
	// is_numeric does sanitization
	$post_ids = array_filter( $post_ids, 'is_numeric' );
	$post_ids = array_map( 'intval', $post_ids );
	if( ! empty( $post_ids ) ) {
		global $wpdb;
		$post_id_list = implode( ',', $post_ids );
		if ( $post_id_list != '-1' ) {
			$views = $wpdb->get_results(
				"SELECT ID as id, post_title
			FROM {$wpdb->posts}
			WHERE post_type = 'view'
			AND id IN ( $post_id_list )"
			);
		} else {
			$views_and_wpas = $wpdb->get_results(
				"SELECT posts.ID as id, posts.post_title, postmeta.meta_value as meta
			FROM {$wpdb->posts} as posts
			LEFT JOIN {$wpdb->postmeta} AS postmeta
			ON ( posts.ID = postmeta.post_id
			AND postmeta.meta_key = '_wpv_settings' )
			WHERE post_type = 'view'
			AND post_status = 'trash'"
			);

			$views = array();

			foreach ( $views_and_wpas as $view ) {
			    $view_meta = unserialize( $view->meta );
				if (
					! isset( $view_meta['view-query-mode'] )
					|| $view_meta['view-query-mode'] == 'normal'
				) {
                    $views[] = $view;
				}
			}
        }
	} else {
		$views = array(); // This should never happen.
	}
	$view_count = count( $views );
	// Different values based on the action we're confirming (they're all here).
	$view_action = wpv_getpost( 'view_action', 'delete', array( 'delete', 'trash' ) );
	$action_word = ( 'delete' == $view_action ) ? __( 'permanently delete', 'wpv-views' ) : __( 'trash', 'wpv-views' );
	ob_start();
	?>
		<div class="wpv-dialog wpv-shortcode-gui-content-wrapper">
			<h3>
				<?php
					printf(
							_n(
								'Are you sure you want to %s this View?',
								'Are you sure you want to %s these Views?',
								$view_count,
								'wpv-views' ),
							$action_word );
				?>
			</h3>
			<p>
				<?php
					echo _n(
							'Please use the Scan button first to be sure that it is not used anywhere.',
							'Please use Scan buttons first to be sure that they are not used anywhere.',
							$view_count,
							'wpv-views' );
				?>
			</p>
			<table class="wpv-view-table" style="width: 100%;">
				<?php
					foreach( $views as $view ) {
						?>
						<tr>
							<td><strong><?php echo esc_html( $view->post_title ); ?></strong></td>
							<td class="wpv-admin-listing-col-scan">
								<button class="button js-scan-button" data-view-id="<?php echo esc_attr( $view->id ); ?>">
									<?php _e( 'Scan', 'wpv-views' ); ?>
								</button>
								<span class="js-nothing-message hidden"><?php _e( 'Nothing found', 'wpv-views' ); ?></span>
							</td>
						</tr>
						<?php
					}
				?>
			</table>
		</div>
	<?php
	$result = ob_get_clean();
	$data = array(
		'dialog_content' => $result,
        'ids' => wp_list_pluck( $views, 'id' )
	);
	wp_send_json_success( $data );
}


/* ************************************************************************** *\
		WP Archive listing screen
\* ************************************************************************** */


// Add up, down or first WP Archive - popup structure

add_action('wp_ajax_wpv_create_wp_archive_popup', 'wpv_create_wp_archive_popup_callback');

function wpv_create_wp_archive_popup_callback() {
	wpv_ajax_authenticate( 'work_views_listing', array( 'parameter_source' => 'get', 'type_of_death' => 'data' ) );
	global $WPV_view_archive_loop;
	ob_start();
	$WPV_view_archive_loop->create_view_archive_popup();
	$response = ob_get_clean();
	$data = array(
		'dialog_content' => $response
	);
	wp_send_json_success( $data );
}


// Change usage for Archive in name arrange callback function

add_action('wp_ajax_wpv_wp_archive_change_usage', 'wpv_wp_archive_change_usage_callback');

function wpv_wp_archive_change_usage_callback() {
	wpv_ajax_authenticate( 'work_views_listing', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	if ( ! isset( $_POST["form"] ) ) {
		$data = array(
			'message' => __( 'Untrusted data', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
    global $WPV_view_archive_loop;
    parse_str( $_POST['form'], $form_data );
	$archive_id = $form_data["wpv-archive-view-id"];
	$WPV_view_archive_loop->update_view_archive_settings( $archive_id, $form_data );
	do_action( 'wpv_action_wpv_save_item', $archive_id );
	wp_send_json_success();
}

// Create WP Archive in usage arrange callback function
// @todo we need to use the API to create this, or at least *create* that API if needed

add_action( 'wp_ajax_wpv_create_wpa_for_archive_loop', 'wp_ajax_wpv_create_wpa_for_archive_loop_callback' );

function wp_ajax_wpv_create_wpa_for_archive_loop_callback() {
	wpv_ajax_authenticate( 'work_views_listing', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	global $wpdb, $WPV_view_archive_loop;
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE ( post_title = %s OR post_name = %s )
			AND post_type = 'view'
			LIMIT 1",
			$_POST["title"],
			$_POST["title"]
		)
	);
	if ( $existing ) {
		$data = array(
			'message'	=> __( 'Another item already uses this name. Please use another name.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}

	$proposed_name = sanitize_text_field( sanitize_title( $_POST["title"] ) );

	if ( empty( $proposed_name ) ) {
		$proposed_name = 'view-rand-' . uniqid();
	}

	$new_archive = array(
		'post_title'    => sanitize_text_field( $_POST["title"] ),
		'post_name'		=> $proposed_name,
		'post_type'     => 'view',
		'post_content'  => "[wpv-filter-meta-html]\n[wpv-layout-meta-html]",
		'post_status'   => 'publish',
		'post_author'   => get_current_user_id(),
		'comment_status' => 'closed'
	);
	$post_id = wp_insert_post( $new_archive );

	$purpose = ( isset( $_POST['purpose'] ) && in_array( $_POST['purpose'], array( 'all', 'parametric' ) ) ) ? $_POST['purpose'] : 'all';

	$archive_defaults = wpv_wordpress_archives_defaults( 'view_settings', $purpose );
	$archive_layout_defaults = wpv_wordpress_archives_defaults( 'view_layout_settings', $purpose );

	update_post_meta( $post_id, '_wpv_settings', $archive_defaults );
	update_post_meta( $post_id, '_wpv_layout_settings', $archive_layout_defaults );

	$form_data = array(
		sanitize_text_field( $_POST['loop'] ) => $post_id
	);
	$WPV_view_archive_loop->update_view_archive_settings( $post_id, $form_data );

	/**
	 * Filter for getting the ID of the created WordPress Archive post.
	 *
	 * @param int                   $wpa_id
	 * @param WPV_WordPress_Archive $wpa
	 *
	 * @since 3.0
	 */
	$post_id = apply_filters( 'wpv_filter_wpa_created', $post_id, \WPV_WordPress_Archive::get_instance( $post_id ) );

	$data = array(
		'id' => $post_id
	);
	wp_send_json_success( $data );
}


/**
 * Render a popup to change WordPress Archive usage.
 *
 * Used in WordPress Archive listing, arranged by usage.
 *
 * Expects following GET arguments:
 * - wpnonce: A valid wpv_wp_archive_arrange_usage nonce.
 * - id: Slug of the loop whose WPA should be changed.
 *
 * @since unknown.
 */
add_action('wp_ajax_wpv_change_wpa_for_archive_loop_popup', 'wpv_change_wpa_for_archive_loop_popup_callback');

function wpv_change_wpa_for_archive_loop_popup_callback() {
	wpv_ajax_authenticate( 'wpv_wp_archive_arrange_usage', array( 'parameter_source' => 'get', 'type_of_death' => 'data' ) );

	if ( ! isset( $_GET["id"] ) ) {
		$data = array(
			'message'	=> __( 'Untrusted data', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	global $WPV_view_archive_loop, $WPV_settings;
	// Slug of the loop.
	$loop_key = sanitize_text_field( $_GET["id"] );
    $loops = $WPV_view_archive_loop->_get_post_type_loops();
	/* We will slightly misuse this function, but it gives us exactly what we need:
	 * a list of published WPAs. */
	$views_pre_query_data = wpv_prepare_view_listing_query(
		array( 'archive' ),
		'publish',
		array( 'posts.post_title' => 'post_title' ), // also give us post title
		true, // return rows from the table
		array( "posts.post_status = 'publish'" )
	); // limit mysql query only to published posts
	$views = $views_pre_query_data['rows'];
	// ID of currently assigned view or 0.
	$currently_assigned_view_id = isset( $WPV_settings[ $loop_key ] ) ? $WPV_settings[ $loop_key ] : 0;
	ob_start();
	?>
	<div class="wpv-dialog wpv-shortcode-gui-content-wrapper js-wpv-dialog-change">
		<h3><?php _e( 'Select a WordPress Archive', 'wpv-views' ); ?></h3>
		<?php wp_nonce_field( 'wpv_view_edit_nonce', 'wpv_view_edit_nonce' ); ?>
		<input type="hidden" value="<?php echo esc_attr( $loop_key ); ?>" id="js-wpv-change-wpa-for-archive-loop-key" name="wpv-archive-loop-key" />
		<ul id="js-wpv-change-wpa-for-archive-loop-list" class="wpv-mightlong-list">
			<li>
				<label>
					<input type="radio" name="wpv-view-loop-archive" value="0" <?php checked( 0 == $currently_assigned_view_id ); ?> />
					<?php _e( 'Don\'t use a WordPress Archive for this loop', 'wpv-views' ); ?>
				</label>
			</li>
			<?php
				foreach ( $views as $view ) {
					?>
					<li>
						<label>
							<input type="radio" <?php checked( $view->id == $currently_assigned_view_id ); ?>
									name="wpv-view-loop-archive" value="<?php echo esc_attr( $view->id ); ?>" />
							<?php echo $view->post_title; ?>
						</label>
					</li>
					<?php
				}
			?>
		</ul>
	</div>
	<?php
	$response = ob_get_clean();
	$data = array(
		'dialog_content' => $response
	);
	wp_send_json_success( $data );
}

// Change WP Archive usage in usage arrange callback function

add_action('wp_ajax_wpv_change_wpa_for_archive_loop', 'wpv_change_wpa_for_archive_loop_callback');

function wpv_change_wpa_for_archive_loop_callback() {
	wpv_ajax_authenticate( 'wpv_wp_archive_arrange_usage', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	global $WPV_settings;
	$loop = sanitize_text_field( $_POST["loop"] );
	$selected = sanitize_text_field( $_POST["selected"] );
	$WPV_settings[$loop] = $selected;
	do_action( 'wpv_action_wpv_delete_transient_shortcodes_gui_views' );
	$settings_array = $WPV_settings->get();
	foreach ( $settings_array as $key => $value ) {
        if ( $value == 0 ) {
            unset( $WPV_settings[$key] );
        }
    }
	$WPV_settings->save();
	wp_send_json_success();
}

// Delete one WPA permanently callback function
// @note it also deletes the loop Template if needed

add_action('wp_ajax_wpv_delete_wpa_permanent', 'wpv_delete_wpa_permanent_callback');

function wpv_delete_wpa_permanent_callback() {
	wpv_ajax_authenticate( 'wpv_remove_view_permanent_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	if (
		! isset( $_POST['id'] )
		|| ! is_numeric( $_POST['id'] )
		|| intval( $_POST['id'] ) < 1
	) {
		$data = array(
			'message' => __( 'Wrong data.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	$loop_content_template = get_post_meta( $_POST['id'], '_view_loop_template', true );
	wp_delete_post( $_POST['id'] );
	if ( ! empty( $loop_content_template ) ) {
		wp_delete_post( $loop_content_template, true );
	}
    // Clean options - when deleting WPA
    global $WPV_settings;
	$WPV_settings->refresh_view_settings_data();
    $WPV_settings->save();
	wp_send_json_success();
}

// Change status of View and WPA callback function TODO use a more generic function name

add_action('wp_ajax_wpv_view_change_status', 'wpv_view_change_status_callback');

function wpv_view_change_status_callback(){
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		die( "Untrusted user" );
	}
	if ( ! (
		wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_listing_actions_nonce' )  // from the Views listing screen OR
		|| wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_change_status' ) // from the View edit screen
	) ) {
		die( "Security check" );
	}
	if (
		! isset( $_POST['id'] )
		|| ! is_numeric( $_POST['id'] )
		|| intval( $_POST['id'] ) < 1
	) {
		die( "Untrusted data" );
	}
	if ( ! isset( $_POST['newstatus'] ) ) {
		$_POST['newstatus'] = 'publish';
	}

	$new_status = sanitize_text_field( $_POST['newstatus'] );
	$post_id = intval( $_POST['id'] );
	$previous_status = get_post_meta( $post_id, '_wp_trash_meta_status', true );
	if ( ! empty( $previous_status ) ) {
		$new_status = $previous_status;
		delete_post_meta( $post_id, '_wp_trash_meta_status' );
		delete_post_meta( $post_id, '_wp_trash_meta_time' );
	}

	$my_post = array(
		'ID'			=> $_POST['id'],
		'post_status'	=> $new_status
	);
	$return = wp_update_post( $my_post );
	if ( isset( $_POST['cleararchives'] ) ) {
		global $WPV_settings;
		$settings_array = $WPV_settings->get();
		if ( ! empty( $settings_array ) ) {
			foreach ( $settings_array as $option_name => $option_value ) {
				if ( strpos( $option_name, 'view_' ) === 0  && $option_value == $_POST['id'] ) {
					$WPV_settings[$option_name] = 0;
				}
			}
			$WPV_settings->save();
		}
	}
	do_action( 'wpv_action_wpv_save_item', $_POST['id'] );
	echo $return;
	die();
}

add_action( 'wp_ajax_wpv_archive_bulk_delete_render_popup', 'wpv_archive_bulk_delete_render_popup_callback' );

/**
 * Callback to be executed before opening the WPA bulk delete popup
 *
 * Currently this only summarizes the ids of the WPA that are going to be deleted.
 * todo: This can be extended to facilitate more functionality.
 *
 * Expected POST parameters:
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 * - ids: An array of WPA IDs
 *
 * @since 2.3
 */
function wpv_archive_bulk_delete_render_popup_callback() {
	wpv_ajax_authenticate( 'wpv_view_listing_actions_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	if( !isset( $_POST['ids'] ) ) {
		$wpa_ids = array();
	} else if( is_string( $_POST['ids'] ) ) {
		$wpa_ids = array( $_POST['ids'] );
	} else {
		$wpa_ids = $_POST['ids'];
	}

	global $wpdb;
	$data = array();

	$wpa_ids = array_map( 'esc_attr', $wpa_ids );
	$wpa_ids = array_map( 'trim', $wpa_ids );
	// is_numeric does sanitization
	$wpa_ids = array_filter( $wpa_ids, 'is_numeric' );
	$wpa_ids = array_map( 'intval', $wpa_ids );

	$data['action'] = 'delete';

	if( sizeof( $wpa_ids ) && $wpa_ids[0] == -1 ) {
		$data['action'] = 'empty-trash';

		$views_and_wpas = $wpdb->get_results(
			"SELECT posts.ID as id, postmeta.meta_value as meta
			FROM {$wpdb->posts} as posts
			LEFT JOIN {$wpdb->postmeta} AS postmeta
			ON ( posts.ID = postmeta.post_id
			AND postmeta.meta_key = '_wpv_settings' )
			WHERE post_type = 'view'
			AND post_status = 'trash'"
		);

		$selected_wpas = array();
		foreach ( $views_and_wpas as $wpa ) {
			$view_meta = unserialize( $wpa->meta );
			if (
				isset( $view_meta['view-query-mode'] )
				&& in_array( $view_meta['view-query-mode'], array( 'archive', 'layouts-loop' ) )
			) {
				$selected_wpas[] = $wpa;
			}
		}


		$wpa_ids = wp_list_pluck( $selected_wpas, 'id' );
	}

	$data['wpa_ids'] = $wpa_ids;

	wp_send_json_success( $data );

}

add_action('wp_ajax_wpv_view_change_post_name', 'wpv_view_change_post_name_callback');

function wpv_view_change_post_name_callback(){
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		wp_send_json_error(
			array( 'message' => 'Untrusted user' )
		);
	}
	if ( ! (
		wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_listing_actions_nonce' )  // from the Views listing screen OR
		|| wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_change_post_name' ) // from the View edit screen
	) ) {
		wp_send_json_error(
			array( 'message' => 'Security check', )
		);
	}

	$view_id  = (int) sanitize_text_field( toolset_getpost( 'id', '' ) );

	if ( $view_id  < 1 ) {
		wp_send_json_error(
			array( 'message' => 'Untrusted data', )
		);
	}



	if (
		! isset( $_POST["post_name"] )
		|| empty( $_POST["post_name"] )
	) {
		$_POST['post_name'] = sanitize_title( get_the_title( $_POST['id'] ) );
	}

	$proposed_name = sanitize_text_field( sanitize_title( $_POST['post_name'] ) );

	if ( empty( $proposed_name ) ) {
		$proposed_name = 'view-rand-' . uniqid();
	}

	$my_post = array(
		'ID'        => $view_id,
		'post_name' => $proposed_name,
	);

	if ( WPV_View_Base::is_name_used( $proposed_name, $view_id ) ) {
		$edited_post = get_post( $view_id );
		$collision_posts = get_posts(
				array(
					'name' => $proposed_name,
					'posts_per_page' => 1,
					'post_type' => $edited_post->post_type,
				)
		);

		if (
				isset( $collision_posts[0] ) &&
				property_exists( $collision_posts[0], 'ID' ) &&
				property_exists( $collision_posts[0], 'post_title' )
		) {
			$view_query_mode = WPV_View_Base::is_archive_view( $collision_posts[0]->ID ) ? __( 'WordPress Archive', 'wpv-views' ) : __( 'View', 'wpv-views' );
			$message = sprintf(
				__( 'Another %1$s (%2$s) already uses this slug. Please use another name.', 'wpv-views' ),
				$view_query_mode,
				sanitize_text_field( $collision_posts[0]->post_title )
			);

			$data = array(
				'message' => $message,
			);
			wp_send_json_error( $data );
		}
	}

	$id = wp_update_post( $my_post );
	$post_data = get_post($id, ARRAY_A);

	wp_send_json_success( array( 'id' => $id, 'slug' => $proposed_name ) );
}

/**
 * Change status of multiple Views, WordPress Archives or Content Templates. Callback function.
 *
 * Following POST parameters are expected:
 *
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 * - newstatus: New status for posts that should be updated. Default is 'publish'.
 * - ids: An array of post IDs that should be updated. Single (string or numeric) value is also accepted.
 * - cleararchives: If set to 1, assignment of givent posts (WPAs) in archive loops will be cleared.
 *
 * Outputs '0' on failure (when one or more posts couldn't be updated) and '1' on success.
 *
 * @since 1.7
 */
add_action( 'wp_ajax_wpv_view_bulk_change_status', 'wpv_view_bulk_change_status_callback' );

function wpv_view_bulk_change_status_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		die( "Untrusted user" );
	}
	if ( ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_listing_actions_nonce' ) ) {
		die( "Security check" );
	}
	$new_status = isset( $_POST['newstatus'] ) ? sanitize_text_field( $_POST['newstatus'] ) : 'publish';
	if ( ! isset( $_POST['ids'] ) ) {
		$post_ids = array();
	} else if ( is_string( $_POST['ids'] ) ) {
		$post_ids = array( $_POST['ids'] );
	} else {
		$post_ids = $_POST['ids'];
	}
	$post_ids = array_map( 'esc_attr', $post_ids );
	$post_ids = array_map( 'trim', $post_ids );
	// is_numeric does sanitization
	$post_ids = array_filter( $post_ids, 'is_numeric' );
	$post_ids = array_map( 'intval', $post_ids );
	// Update post statuses
	$is_failure = false;
	foreach ( $post_ids as $post_id ) {
		$my_post = array(
				'ID' => $post_id,
				'post_status' => $new_status );
		$res = wp_update_post( $my_post );
		$is_failure = $is_failure || ( $res == 0 );
		do_action( 'wpv_action_wpv_save_item', $post_id );
	}
	// Clear archive loop assignment, if requested
	if ( isset( $_POST['cleararchives'] ) && ( 1 == $_POST['cleararchives'] ) ) {
		global $WPV_settings;
		$settings_array = $WPV_settings->get();
		if ( ! empty( $settings_array ) ) {
			foreach ( $settings_array as $option_name => $option_value ) {
				if ( ( strpos( $option_name, 'view_' ) === 0 ) && in_array( $option_value, $post_ids ) ) {
					$WPV_settings[ $option_name ] = 0;
				}
			}
			$WPV_settings->save();
		}
	}
	echo $is_failure ? 0 : 1;
	die();
}


/**
 * Permanently delete multiple Views or WordPress Archives. Callback function.
 *
 * Needs a wpv_bulk_remove_view_permanent_nonce to be present.
 * Also deletes associated loop Templates if any
 *
 * Outputs 0 on failure (when one or more posts couldn't be deleted) and 1 on success.
 *
 * $since 1.7
 */
add_action( 'wp_ajax_wpv_bulk_delete_views_permanent', 'wpv_bulk_delete_views_permanent_callback' );

function wpv_bulk_delete_views_permanent_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		die( "Untrusted user" );
	}
	if ( ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_bulk_remove_view_permanent_nonce' ) ) {
		die( "Security check" );
	}
	global $wpdb;
	if ( ! isset( $_POST['ids'] ) ) {
		$post_ids = array();
	} else if ( is_string( $_POST['ids'] ) ) {
		$post_ids = array( $_POST['ids'] );
	} else {
		$post_ids = $_POST['ids'];
	}
	$post_ids = array_map( 'esc_attr', $post_ids );
	$post_ids = array_map( 'trim', $post_ids );
	// is_numeric does sanitization
	$post_ids = array_filter( $post_ids, 'is_numeric' );
	$post_ids = array_map( 'intval', $post_ids );
	$is_failure = false;
	// Delete loop Templates if any
	if ( count( $post_ids ) > 0 ) {
		$remove_loop_templates = " AND post_id IN (" . implode( "," , $post_ids ) . ") ";
		$remove_loop_templates_ids = $wpdb->get_col(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
			WHERE meta_key = '_view_loop_template'
			AND meta_value != '0'
			{$remove_loop_templates}"
		);
		foreach ( $remove_loop_templates_ids as $remove_template ) {
			wp_delete_post( $remove_template, true );
		}
	}
	foreach ( $post_ids as $post_id ) {
		$res = wp_delete_post( $post_id );
		$is_failure = $is_failure || ( $res == false );
	}
	// Clean options - when deleting WPA
	global $WPV_settings;
	$WPV_settings->refresh_view_settings_data();
    $WPV_settings->save();
	echo $is_failure ? 0 : 1;
	die();
}


/**
 * Find out which WordPress Archives are used in some loops.
 *
 * For given WPA IDs output those who are used in archive loops. If there are any, also
 * generate HTML for the colorbox popup - confirmation to trash them.
 *
 * Following GET parameters are expected:
 * - wpnonce: Valid wpv_view_listing_actions_nonce.
 * - ids: An array of WPA IDs that should be checked.
 *
 * Output is a JSON representation of an array with following elements:
 * - used_wpa_ids: An array of IDs of WPAs in use.
 * - colorbox_html: If used_wpa_ids is non-empty, this contains HTML for the colorbox popup.
 *     When user confirms it, *all* of the WPAs will be trashed (not only those from used_wpa_ids).
 *     Otherwise it is an empty string.
 *
 * @since 1.7
 */
add_action( 'wp_ajax_wpv_archive_check_usage', 'wpv_archive_check_usage_callback' );

function wpv_archive_check_usage_callback() {
	wpv_ajax_authenticate( 'wpv_view_listing_actions_nonce', array( 'parameter_source' => 'get', 'type_of_death' => 'data' ) );

	if( ! isset( $_GET['ids'] ) ) {
		$post_ids = array();
	} else if( is_string( $_GET['ids'] ) ) {
		$post_ids = array( $_GET['ids'] );
	} else {
		$post_ids = $_GET['ids'];
	}
	$post_ids = array_map( 'esc_attr', $post_ids );
	$post_ids = array_map( 'trim', $post_ids );
	// is_numeric does sanitization
	$post_ids = array_filter( $post_ids, 'is_numeric' );
	$post_ids = array_map( 'intval', $post_ids );
	$nonce = wp_create_nonce( 'wpv_view_listing_actions_nonce' );
	global $WPV_settings, $WPV_view_archive_loop;
	$loops = $WPV_view_archive_loop->_get_post_type_loops();
	$taxonomies = get_taxonomies( '', 'objects' );
	$exclude_tax_slugs = array();
	$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
	// This will hold IDs of used archives.
	$archive_ids_in_use = array();
	// Check for usage in loops
	foreach ( $loops as $loop => $loop_name ) {
		if ( isset( $WPV_settings[ 'view_' . $loop ] )
			&& in_array( $WPV_settings[ 'view_' . $loop ], $post_ids ) )
		{
			$used_archive_id = $WPV_settings[ 'view_' . $loop ];
			// Use post id for both key and value to ensure it will be present only once as value.
			$archive_ids_in_use[ $used_archive_id ] = $used_archive_id;
		}
	}
	// Check for usage in taxonomies
	foreach ( $taxonomies as $category_slug => $category ) {
		if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
			continue;
		}
		// Only show taxonomies with show_ui set to TRUE
		if ( !$category->show_ui ) {
			continue;
		}
		$name = $category->name;
		if ( isset ( $WPV_settings[ 'view_taxonomy_loop_' . $name ] )
			&& in_array( $WPV_settings[ 'view_taxonomy_loop_' . $name ], $post_ids ) )
		{
			$used_archive_id = $WPV_settings[ 'view_taxonomy_loop_' . $name ];
			$archive_ids_in_use[ $used_archive_id ] = $used_archive_id;
		}
	}
	$archive_ids_in_use = array_map( 'esc_attr', $archive_ids_in_use );
	$archive_ids_in_use = array_map( 'trim', $archive_ids_in_use );
	// is_numeric does sanitization
	$archive_ids_in_use = array_filter( $archive_ids_in_use, 'is_numeric' );
	$archive_ids_in_use = array_map( 'intval', $archive_ids_in_use );
	// If there are some used archives, generate html for the colorbox confirmation popup
	if ( ! empty( $archive_ids_in_use ) ) {
		// We only get IDs and titles
		global $wpdb;
		$used_archive_id_list = implode( ',', $archive_ids_in_use );
		$used_archives = $wpdb->get_results(
			"SELECT ID as id, post_title FROM {$wpdb->posts}
			WHERE post_type = 'view'
			AND id IN ( $used_archive_id_list )"
		);
		$used_archive_count = count( $archive_ids_in_use );
		ob_start();
		?>
		<div class="wpv-dialog wpv-shortcode-gui-content-wrapper js-bulk-trash-archives-dialog">
			<h3>
				<?php
					echo _n(
							'Are you sure you want to trash this WordPress Archive?',
							'Are you sure you want to trash these WordPress Archives?',
							$used_archive_count,
							'wpv-views' );
				?>
			</h3>
			<p>
				<?php
					echo _n(
							'It is assigned to one or more archive or taxonomy loops.',
							'Some of them are assigned to archive or taxonomy loops.',
							$used_archive_count,
							'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. _n(
							'Trashing it will also unassign it.',
							'Trashing them will also unassign them.',
							$used_archive_count,
							'wpv-views' );
				?>
			</p>
			<ul style="list-style-type: disc; padding-left: 40px;">
				<?php
					foreach( $used_archives as $archive ) {
						?>
							<li><strong><?php echo $archive->post_title; ?></strong></li>
						<?php
					}
				?>
			</ul>
		</div> <!-- .js-bulk-trash-archives-dialog -->
		<?php
		$dialog_content = ob_get_contents();
		ob_end_clean();
	} else {
		$dialog_content = '';
	}
	$data = array(
		'dialog_content'	=> $dialog_content,
		'used_wpa_ids'		=> $archive_ids_in_use
	);
	wp_send_json_success( $data );
}


/* ************************************************************************* *\
        Content Templates
\* ************************************************************************* */


add_action( 'wp_ajax_wpv_apply_ct_to_cpt_posts_popup', 'wpv_apply_ct_to_cpt_posts_popup_callback');


/**
 * Callback function for the AJAX action wp_ajax_wpv_ct_update_posts used to count dissident posts that are not using
 * the Template assigned to its type. This is called on the Content Templates listing screen for single usage
 * and on the Template edit screen.
 *
 * @since 1.3.0
 */
function wpv_apply_ct_to_cpt_posts_popup_callback() {
	wpv_ajax_authenticate( 'work_view_template', array( 'parameter_source' => 'get', 'type_of_death' => 'data' ) );

    if (
		isset ( $_GET['type'] )
		&& isset( $_GET['id'] )
	) {
        $type = sanitize_text_field( $_GET['type'] );
        $id = intval( $_GET['id'] );
    } else {
		$data = array(
			'message' => __( 'Wrong data.', 'wpv-views' )
		);
		wp_send_json_error( $data );
		return;
    }

	try {
        $ct = new WPV_Content_Template_Embedded( $id );
    } catch( Exception $e ) {
        // well, we were not handling non-existent CTs before and I am not sure how to do that now...
        $data = array(
			'message' => __( 'Wrong data.', 'wpv-views' )
		);
		wp_send_json_error( $data );
		return;
    }

    $dissident_post_count = $ct->get_dissident_posts( $type, 'count' );
    if ( $dissident_post_count > 0 ) {
        $ptype = get_post_type_object( $type );

        if ( $dissident_post_count > 1 ){
            $type_label = $ptype->labels->name;
            $message = '<p>' . sprintf( __( '<strong>%d %s</strong> use a different Content Template.', 'wpv-views' ), $dissident_post_count , $type_label ) . '</p>';
        } else {
            $type_label = $ptype->labels->singular_name;
            $message = '<p>' . sprintf( __( '<strong>%d %s</strong> uses a different Content Template.', 'wpv-views' ), $dissident_post_count, $type_label ) . '</p>';
        }
		$message .= '<p>'
			. __( 'Maybe you have manually set a different one on them.', 'wpv-views' )
			. WPV_MESSAGE_SPACE_CHAR
			. sprintf(
					__( 'Click <em>Update</em> to force this Content Template into all single %s', 'wpv-views' ),
					$type_label
				)
			. '</p>';
		$data = array(
			'dialog_content' => '<div class="wpv-dialog wpv-shortcode-gui-content-wrapper">' . $message . '</div>'
		);
		wp_send_json_success( $data );
        ?>
    <?php
    } else {
		$data = array(
			'message' => __( 'Wrong data.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
}

// Unlink a Content Template for orphaned single posts types when there is no general Template associated with that type

add_action('wp_ajax_wpv_clear_cpt_from_ct', 'wpv_clear_cpt_from_ct_callback');

function wpv_clear_cpt_from_ct_callback() {
	wpv_ajax_authenticate( 'wpv_clear_cpt_from_ct_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );
	if ( ! isset( $_POST['slug'] ) ) {
		$data = array(
			'message' => __( 'Slug not set in the AJAX call.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	} else {
		global $wpdb;
		$type = $_POST['slug'];
		$posts = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}
				WHERE post_type = %s",
				$type
			)
		);
		$count = sizeof( $posts );
		if ( $count > 0 ) {
			foreach( $posts as $post_id ) {
				WPV_Content_Template_Embedded::remove_ct_assignment_from_post_object( $post_id );
			}
		}
		wp_send_json_success();
	}
}

/*
 * Add new Content Template - popup structure
 * Added by Gen TODO check this nonce
 */

add_action('wp_ajax_wpv_ct_create_new_render_popup', 'wpv_ct_create_new_render_popup_callback');

/**
 * Render HTML for the Add new Content Template dialog.
 *
 * @since unknown
 */
function wpv_ct_create_new_render_popup_callback(){
    wpv_ajax_authenticate( 'work_view_template', array( 'parameter_source' => 'get', 'type_of_death' => 'data' ) );

	// @todo None of those parameters are passed on the AJAX Get call at all...
    $ct_title		= wpv_getget( 'ct_title', '' );
    $ct_id_selected	= intval( wpv_getget( 'ct_selected', 0 ) );
    $ct_selected	= WPV_Content_Template::get_instance( $ct_id_selected );

	ob_start();
	?>
    <div class="wpv-dialog wpv-shortcode-gui-content-wrapper js-wpv-dialog-add-new-content-template wpv-dialog-add-new-content-template">
		<label for="wpv-new-content-template-name">
			<strong><?php _e('Name this Content Template','wpv-views') ?></strong>
		</label>
		<input type="text" value="<?php echo esc_attr( $ct_title ); ?>" id="wpv-new-content-template-name" class="js-wpv-new-content-template-name wpv-new-content-template-name" placeholder="<?php echo esc_attr( __('Content Template name','wpv-views') ) ?>" name="wpv-new-content-template-name">
		<h3><?php _e('What content will this template be for?','wpv-views') ?></h3>
		<p>
			<?php
			_e( 'A Content Template can replace the content of the post with whatever you put into it, including Views shortcodes. ', 'wpv-views' );
			?>
		</p>

		<?php
			wpv_render_ct_assignment_sections( $ct_selected );
		?>
		<div class="js-wpv-error-container"></div>
    </div> <!-- wpv-dialog -->
    <?php
	$response = ob_get_clean();
	$data = array(
		'dialog_content' => $response
	);
	wp_send_json_success( $data );
}

/**
 * Delete CT callback function.
 *
 * DEPRECATED since 1.8. Use wpv_ct_bulk_delete instead!
 *
 * Not used in Views, but I'm not sure about other plugins.
 *
 * @see wpv_ct_bulk_delete_callback()
 */
add_action('wp_ajax_wpv_delete_ct', 'wpv_delete_ct_callback');

function wpv_delete_ct_callback(){
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		die( "Untrusted user" );
	}
    if (
		! isset( $_POST["wpnonce"] )
		|| ! wp_verify_nonce($_POST["wpnonce"], 'work_view_template')
	) {
		die( "Undefined Nonce" );
	}

    $tid = intval( $_POST['id'] );
    global $WPV_settings;
	$settings_array = $WPV_settings->get();
    foreach ( $settings_array as $key => $value ) {
        if ( $value == $tid ) {
            $WPV_settings[$key] = 0;
        }
    }

    $WPV_settings->save();

    wp_delete_post( $tid );
    echo $tid;
    die();
}

// Change CT usage - popup structure TODO review this nonces

add_action('wp_ajax_wpv_change_ct_usage_popup', 'wpv_change_ct_usage_popup');

/**
 * Render HTML for the dialog to change CT assignment.
 *
 * Expects following GET parameters:
 * - wpnonce: A valid work_view_template nonce.
 * - id: Valid ID of a Content Template
 *
 * @since unknown
 */
function wpv_change_ct_usage_popup() {
    wpv_ajax_authenticate( 'work_view_template', array( 'parameter_source' => 'get', 'type_of_death' => 'data' ) );

    $id = intval( wpv_getget( 'id', 0 ) );
    $ct = WPV_Content_Template::get_instance( $id );
    if ( null == $ct ) {
        $data = array(
			'type' => 'error',
			'message' => __( 'Invalid CT ID.', 'wpv-views' )
		);
		wp_send_json_error( $data );
    }
	ob_start();
    ?>
    <div class="wpv-dialog wpv-shortcode-gui-content-wrapper js-wpv-dialog-add-new-content-template wpv-dialog-add-new-content-template">
        <h3><?php _e('What content will this template be for?','wpv-views') ?></h3>
		<form method="" id="wpv-add-new-content-template-form">
             <?php wpv_render_ct_assignment_sections( $ct ); ?>
        </form>
	</div>
    <?php
	$response = ob_get_clean();
	$data = array(
		'dialog_content' => $response
	);
	wp_send_json_success( $data );
}


/**
 * Render part of the dialog for assigning CT to different loops/post types.
 *
 * This will render the "toggle" section headers and section content with checkboxes. If a CT is provided,
 * checkbox value and section toggle states will be adjusted to it's current settings.
 *
 * This code is being used in "Add new Content Template" and "Change template usage" dialogs.
 *
 * @param WPV_Content_Template|null $ct Existing CT for default values or null.
 *
 * @since 1.10
 */
function wpv_render_ct_assignment_sections( $ct = null ) {

    global $WPV_view_archive_loop;
    $single_post_type_loops	= $WPV_view_archive_loop->get_archive_loops( 'post_type', false, true, true );
    $post_type_loops		= $WPV_view_archive_loop->get_archive_loops( 'post_type', false, true );
    $taxonomy_archive_loops	= $WPV_view_archive_loop->get_archive_loops( 'taxonomy', false, true );

    $asterisk				= '<span style="color:red;">*</span>';
    $asterisk_explanation	= __( '<span style="color:red">*</span> A different Content Template is already assigned to this item', 'wpv-views' );

    $has_ct = ( null != $ct );

    //
    // Section for single post type assignment
    //

    $show_asterisk_explanation	= false;
	$apply_to_existing_label	= __( 'Apply to existing %s items', 'wpv-views' );
	?>
	<div style="display:table;width:100%;">
	<div style="display:table-row">

	<div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list" style="display:table-cell;width:33%">
		<h4><?php echo __( 'Single pages', 'wpv-views' ); ?></h4>
		<?php
		if ( count( $single_post_type_loops ) > 0 ) {
		?>
        <ul>
			<?php
			if ( ! $has_ct ) {
				?>
			<li class="js-wpv-ct-assign-to-object-container">
				<input id="wpv-content-template-no-use" type="checkbox" class="js-wpv-ct-assign-to-object js-wpv-ct-assign-to-post-type-single js-wpv-ct-dont-assign" <?php checked( true ); ?> name="wpv-new-content-template-post-type[]" value="0" />
				<label for="wpv-content-template-no-use"><?php _e("Don't assign to any post type",'wpv-views') ?></label>
			</li>
				<?php
			}
        foreach ( $single_post_type_loops as $post_type_loop ) {
            $post_type		= $post_type_loop['post_type_name'];
			$singular_name	= $post_type_loop['singular_name'];
            $setting_name	= 'views_template_for_' . esc_attr( $post_type );
            $assigned_ct_id	= $post_type_loop['single_ct'];
            $type_current	= $type_used = false;

			$input_ids		= $setting_name;
			if ( $has_ct ) {
				$input_ids .= '-' . $ct->id;
			} else {
				$input_ids .= '-0';
			}

			$apply_to_current = sprintf(
				$apply_to_existing_label,
				$singular_name
			);

            if ( $assigned_ct_id != 0 ) {
                $type_used = true;
                $show_asterisk_explanation = true;
            }
            if (
				$has_ct
				&& $ct->id == $assigned_ct_id
			) {
                $type_current = true;
                $type_used = false;
                $open_section = true;
            }

			/** @noinspection HtmlUnknownAttribute */
			printf(
                '<li class="js-wpv-ct-assign-to-object-container">
                    <input id="%s" type="checkbox" class="js-wpv-ct-assign-to-object js-wpv-ct-assign-to-post-type-single" name="wpv-new-content-template-post-type[]" %s data-title="%s" value="%s" />
                    <label for="%s">%s%s</label>
					<div class="js-wpv-ct-assign-to-all-existing" style="margin-left:20px;margin-top:5px;font-size:11px;%s">
					<input id="%s-apply" type="checkbox" class="js-wpv-ct-assign-to-object-apply js-wpv-ct-assign-to-post-type-single-apply" name="wpv-new-content-template-post-type-apply[]" %s value="%s" />
                    <label for="%s-apply">%s</label>
					</div>
                </li>',
                $input_ids, checked( $type_current, true, false ), esc_attr( $post_type_loop['display_name'] ), $setting_name,
                $input_ids, $post_type_loop['display_name'], ( $type_used ? $asterisk : '' ),
				( $type_current ? '' : 'display:none;' ),
				$input_ids, checked( true, true, false ), $post_type,
                $input_ids, $apply_to_current
            );

        }

        echo '</ul>';

    } else {
        _e( 'There are no single post types to assign Content Templates to', 'wpv-views' );
    }
    ?>
    </div>

    <div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list" style="display:table-cell;width:33%">
		<h4><?php echo __( 'Post type archives', 'wpv-views' ); ?></h4>
		<?php
		if ( count( $post_type_loops ) > 0 ) {

        echo '<ul>';
        foreach ( $post_type_loops as $post_type_loop ) {
            $post_type = $post_type_loop['post_type_name'];
            $setting_name = 'views_template_archive_for_' . esc_attr( $post_type );
            $assigned_ct_id = $post_type_loop['ct'];
            $type_current = $type_used = false;
            if ( $assigned_ct_id != 0 ) {
                $type_used = true;
                $show_asterisk_explanation = true;
            }
            if (
				$has_ct
				&& $ct->id == $assigned_ct_id
			) {
                $type_current = true;
                $type_used = false;
                $open_section = true;
            }

			$input_ids = $setting_name;
			if ( $has_ct ) {
				$input_ids .= '-' . $ct->id;
			} else {
				$input_ids .= '-0';
			}

			/** @noinspection HtmlUnknownAttribute */
			printf(
                '<li class="js-wpv-ct-assign-to-object-container">
                    <input id="%s" type="checkbox" class="js-wpv-ct-assign-to-object js-wpv-ct-assign-to-post-type-archive" name="wpv-new-content-template-post-type[]" %s data-title="%s" value="%s" />
                    <label for="%s">%s%s</label>
                </li>',
                $input_ids, checked( $type_current, true, false ), esc_attr( $post_type_loop['display_name'] ), $setting_name,
                $input_ids, $post_type_loop['display_name'], ( $type_used ? $asterisk : '' )
            );

        }

        echo '</ul>';

    } else {
        _e( 'There are no post type archives to assign Content Templates to', 'wpv-views' );
    }


    ?>
    </div>

    <div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list" style="display:table-cell;width:33%">
		<h4><?php echo __( 'Taxonomy archives', 'wpv-views' ); ?></h4>
		<?php
		if ( count( $taxonomy_archive_loops ) > 0 ) {
        echo '<ul>';

        foreach ( $taxonomy_archive_loops as $taxonomy_archive_loop ) {

            $type = $taxonomy_archive_loop['slug'];
            $label = $taxonomy_archive_loop['display_name'];
            $setting_name = 'views_template_loop_' . $type;
            $assigned_ct_id = $taxonomy_archive_loop['ct'];
            $type_current = $type_used = false;

            if ( 0 != $assigned_ct_id ) {
                $type_used = true;
                $show_asterisk_explanation = true;
            }
            if (
				$has_ct
				&& $ct->id == $assigned_ct_id
			) {
                $type_current = true;
                $type_used = false;
                $open_section = true;
            }

			$input_ids = $setting_name;
			if ( $has_ct ) {
				$input_ids .= '-' . $ct->id;
			} else {
				$input_ids .= '-0';
			}

			/** @noinspection HtmlUnknownAttribute */
			printf(
                '<li class="js-wpv-ct-assign-to-object-container">
                    <input id="%s" type="checkbox" class="js-wpv-ct-assign-to-object js-wpv-ct-assign-to-taxonomy-archive" name="wpv-new-content-template-post-type[]" %s data-title="%s" value="%s" />
                    <label for="%s">%s%s</label>
                </li>',
                $input_ids, checked( $type_current, true, false ), esc_attr( $label ), $setting_name,
                $input_ids, $label, ( $type_used ? $asterisk : '' )
            );

        }

        echo '</ul>';

    } else {
        _e( 'There are no taxonomy archives to assign Content Templates to', 'wpv-views' );
    }
    ?>
    </div>

	</div><!-- table-row -->
	</div><!-- table -->
    <?php
	if ( $show_asterisk_explanation ) {
		?>
		<p>
			<span class="wpv-asterisk-explanation"><?php echo $asterisk_explanation; ?></span>
		</p>
		<?php
	}
	?>

	<?php
}

add_action('wp_ajax_wpv_change_ct_usage', 'wpv_change_ct_usage_callback');

function wpv_change_ct_usage_callback() {
	wpv_ajax_authenticate( 'work_view_template', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

    global $WPV_settings;
    $id = intval( $_POST['view_template_id'] );

    if (
		isset( $_POST['type'] )
		&& is_array( $_POST['type'] )
	) {
        $type = array_map( 'sanitize_text_field', $_POST['type'] );
    } else {
        $type = array();
    }

	if (
		isset( $_POST['apply'] )
		&& is_array( $_POST['apply'] )
	) {
        $apply = array_map( 'sanitize_text_field', $_POST['apply'] );
    } else {
        $apply = array();
    }

	$settings_array = $WPV_settings->get();
    foreach ( $settings_array as $key => $value ) {
        if ( $value == $id ) {
            $WPV_settings[$key] = 0;
        }
    }

    for ( $i = 0; $i < count( $type ); $i++ ) {
        $WPV_settings[$type[$i]] = $id;
    }

    $WPV_settings->save();

	$content_template = WPV_Content_Template::get_instance( $id );
	$content_template->kill_dissident_posts( $apply );

	do_action( 'wpv_action_wpv_save_item', $id );
    wp_send_json_success();
}

// Change CT action - popup structure

add_action('wp_ajax_wpv_change_ct_assigned_to_something_dialog', 'wpv_change_ct_assigned_to_something_dialog_callback');

function wpv_change_ct_assigned_to_something_dialog_callback(){
    wpv_ajax_authenticate( 'work_view_template', array( 'parameter_source' => 'get', 'type_of_death' => 'data' ) );

    global $wpdb, $WPV_settings;
	$values_to_prepare = array();
	$exclude_loop_templates = '';
	$exclude_loop_templates_ids = wpv_get_loop_content_template_ids();
	if ( count( $exclude_loop_templates_ids ) > 0 ) {
		$exclude_loop_templates_ids_sanitized = array_map( 'esc_attr', $exclude_loop_templates_ids );
		$exclude_loop_templates_ids_sanitized = array_map( 'trim', $exclude_loop_templates_ids_sanitized );
		// is_numeric + intval does sanitization
		$exclude_loop_templates_ids_sanitized = array_filter( $exclude_loop_templates_ids_sanitized, 'is_numeric' );
		$exclude_loop_templates_ids_sanitized = array_map( 'intval', $exclude_loop_templates_ids_sanitized );
		if ( count( $exclude_loop_templates_ids_sanitized ) > 0 ) {
			$exclude_loop_templates = " AND p.ID NOT IN ('" . implode( "','" , $exclude_loop_templates_ids_sanitized ) . "') ";
		}
	}
	$values_to_prepare[] = 'view-template';
	$view_tempates_available = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, p.post_title
			FROM {$wpdb->posts} p
			WHERE p.post_status = 'publish'
			AND p.post_type = %s
			{$exclude_loop_templates}
			ORDER BY p.post_title",
			$values_to_prepare
		)
	);

    $post_type = sanitize_text_field( $_GET['pt'] );
	$selected = ( isset( $WPV_settings[$post_type] ) ) ? $WPV_settings[$post_type] : 0;
	ob_start();
    ?>
    <div class="wpv-dialog wpv-shortcode-gui-content-wrapper js-wpv-dialog-add-new-content-template wpv-dialog-add-new-content-template">
		<h3><?php _e( 'Select one of the following Content Templates', 'wpv-views' ); ?></h3>
		<ul class="wpv-mightlong-list">
			<li>
				<input type="radio" id="wpv-content-template-name-0" class="js-wpv-content-template-name" name="wpv-content-template-name" <?php checked( 0, $selected ); ?> value="0" />
				<label for="wpv-content-template-name-0"><?php _e( 'Don’t use any Content Template', 'wpv-views' ); ?></label>
			</li>
			<?php
			if ( count( $view_tempates_available ) > 0 ) {
				foreach ( $view_tempates_available as $row ) {
					?>
						<li>
							<input type="radio" id="wpv-content-template-name-<?php echo esc_attr( $row->ID );?>" class="js-wpv-content-template-name" name="wpv-content-template-name" <?php checked( $row->ID, $selected ); ?> value="<?php echo esc_attr( $row->ID );?>" />
							<label for="wpv-content-template-name-<?php echo esc_attr( $row->ID );?>"><?php echo $row->post_title; ?></label>
						</li>
					<?php
				}
			}
			?>
		</ul>
    </div>
    <?php
	$response = ob_get_clean();
	$data = array(
		'dialog_content' => $response
	);
	wp_send_json_success( $data );
}

// Change CT action callback function TODO check nonces

add_action('wp_ajax_wpv_change_ct_assigned_to_something', 'wpv_change_ct_assigned_to_something_callback');

function wpv_change_ct_assigned_to_something_callback() {
	wpv_ajax_authenticate( 'work_view_template', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

    global $WPV_settings;
    $pt = sanitize_text_field( $_POST["pt"] );
    if ( isset( $_POST['value'] ) ) {
        $value = intval( $_POST['value'] );
        if( $value > 0 ) {
            do_action( 'wpv_action_wpv_save_item', $value );
        }
    } else {
        $value = 0;
    }
	$old_value = $WPV_settings[ $pt ];
	if ( intval( $old_value ) > 0 && isset( $_POST['pt_slug'] ) ) {
		$pt_slug = $_POST['pt_slug'];
		$toolset_dic = apply_filters( 'toolset_dic', false );
		/** @var ContentTemplateService $service */
		$service = $toolset_dic->make( ContentTemplateService::class );
		$service->clean_post_type_assigned_posts( wp_unslash( $pt_slug ), $old_value );
	}

    $WPV_settings[$pt] = $value;
    $WPV_settings->save();

    wp_send_json_success();
}

// Response when updating all posts to use a given CT - popup structure TODO localize!!!! and check nonce
// TODO seems that this is called in a colorbox callback, but BUT is executes the delete... TODO review this all

add_action('wp_ajax_wpv_apply_ct_to_cpt_posts', 'wpv_apply_ct_to_cpt_posts_callback');

function wpv_apply_ct_to_cpt_posts_callback() {
	wpv_ajax_authenticate( 'work_view_template', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

    $id = intval( $_POST['id'] );
    $type = sanitize_text_field( $_POST['type'] );
    wpv_update_dissident_posts_from_template( $id, $type, false );

    wp_send_json_success();
}

add_action('wp_ajax_wpv_content_template_move_to_trash', 'wpv_content_template_move_to_trash_callback');

/** Move CT to trash or show message.
 *
 * Prints a JSON array. If CT is not in use, it is trashed and first element of the array is "move" and second one
 * contains ID of the CT. Otherwise first element is "show" and second contains HTML for the colorbox dialog that should
 * be shown.
 *
 * @deprecated Not used in Views since 1.10. Use wpv_bulk_content_templates_move_to_trash instead.
 */
function wpv_content_template_move_to_trash_callback() {
	wpv_ajax_authenticate( 'wpv_view_listing_actions_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	if ( isset( $_POST['id'] ) ) {
		$ct_id = intval( $_POST['id'] );
	} else {
		$data = array(
			'type' => 'error',
			'message' => __( 'Invalid CT ID.', 'wpv-views' )
		);
		wp_send_json_error( $data );
		return;
	}
	global $wpdb;
	$posts_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT count(posts.ID) FROM {$wpdb->posts} as posts,{$wpdb->postmeta} as postmeta
			WHERE postmeta.meta_key = '_views_template'
			AND postmeta.meta_value = %s
			AND postmeta.post_id = posts.ID",
			$ct_id
		)
	);
	if ( $posts_count == 0 ) {

		$my_post = array(
			'ID'          => $ct_id,
			'post_status' => 'trash'
		);
		wp_update_post( $my_post );
		do_action( 'wpv_action_wpv_save_item', $ct_id );
		wpv_replace_views_template_options( $ct_id, 0 );

		$data = array(
			'action' => 'reload'
		);
		wp_send_json_success( $data );
	} else {
		$template_list = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				AND post_type = 'view-template'
				AND ID != %s",
				$ct_id
			)
		);
		ob_start();
		?>
		<div class="wpv-dialog wpv-shortcode-gui-content-wrapper">
			<p>
				<?php echo sprintf( _n('1 item', '%s items', $posts_count, 'wpv-views'), $posts_count ) . __( ' use this content template. What do you want to do?', 'wpv-views' ); ?>
			</p>
			<ul>
                <?php if ( count($template_list) > 0 ){?>
                <li><label>
                    <input type="radio" name="wpv-content-template-replace-to" class="js-wpv-existing-posts-ct-replace-to js-wpv-existing-posts-ct-replace-to-selected-ct" value="0" id="wpv-content-template-replace-to" />
                    <?php _e( 'Choose a different content template for them: ', 'wpv-views' )?>
                    </label>
                    <select name="wpv-ct-list-for-replace" class="js-wpv-ct-list-for-replace" id="wpv-ct-list-for-replace">
                    	<option value=''><?php _e( 'Select Content Template', 'wpv-views' )?> </option>
                    <?php
						foreach( $template_list as $temp_post ) :
							echo '<option value="'. esc_attr( $temp_post->ID ).'">'. $temp_post->post_title .'</option>';
                        endforeach;
					?></select>

                </li>
                <?php }?>
                <li><label>
                    <input type="radio" name="wpv-content-template-replace-to" class="js-wpv-existing-posts-ct-replace-to" value="1" />
                    <?php _e( 'Don\'t use any content template for these items', 'wpv-views' )?>
                    </label>
                </li>
             </ul>
        </div>
        <?php
        $response = ob_get_clean();
		$data = array(
			'action'			=> 'dialog',
			'dialog_content'	=> $response
		);
		wp_send_json_success( $data );
	}
}



add_action( 'wp_ajax_wpv_bulk_content_templates_move_to_trash', 'wpv_bulk_content_templates_move_to_trash_callback' );


/**
 * Initiate a bulk trash action.
 *
 * For given content templates, find out how many posts use them. If no template is used in any post,
 * trash them right away. If one or more templates is being used, render HTML for a colorbox dialog
 * (wpv-dialog-bulk-replace-content-template) that will ask user to decide how to replace those templates.
 * See js events on '.js-ct-bulk-replace-usage' for further information.
 *
 * Expected POST parameters:
 * - ids: A non-empty array of content template IDs.
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 * @todo use dedicated nonce containing user and CT id; note that this is used also on CT edit page
 *
 * Response is an JSON object containing following properties:
 * - all_ids: Array of all content templates that were/should be trashed.
 * - action: 'trashed' if CTs have been trashed, 'confirm' if a popup should be shown before that.
 * - popup_content: If action is 'confirm', this will contain the HTML of the popup.
 *
 * @since 1.7
 */
function wpv_bulk_content_templates_move_to_trash_callback() {
	wpv_ajax_authenticate( 'wpv_view_listing_actions_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

    // Parse and validate input
    $ct_ids = wpv_getpost( 'ids', array() );
    if( !is_array( $ct_ids ) ) {
        $ct_ids = array( $ct_ids );
    }

    $ct_ids = array_map( 'esc_attr', $ct_ids );
    $ct_ids = array_map( 'trim', $ct_ids );
    // is_numeric does sanitization
    $ct_ids = array_filter( $ct_ids, 'is_numeric' );
    $ct_ids = array_map( 'intval', $ct_ids );

	if ( empty( $ct_ids ) ) {
		// We don't allow empty input
		$data = array(
			'type' => 'error',
			'message' => __( 'Invalid CT ID.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}


	$data = array(
        'all_ids' => $ct_ids,
        'success' => true
    );

	// Determine which templates are currently in use.
	// This can get quite expensive... can we do it in more effective way?

	// This will hold information about used templates indexed by their IDs
	$used_templates = array();
	foreach( $ct_ids as $ct_id ) {

		$ct = WPV_Content_Template::get_instance( $ct_id );

		if( null == $ct ) {
			$data = array(
				'type' => 'error',
				'message' => __( 'Invalid CT ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		$posts_using_ct = $ct->get_posts_using_this( '*', 'count' );

		if( $posts_using_ct > 0 ) {
			$used_templates[ $ct_id ] = array(
				'title' => $ct->title,
				'usage_count' => $posts_using_ct
			);
		}
	}

	if ( empty( $used_templates ) ) {
		// No template is used, we can trash them all.

		$settings = WPV_Settings::get_instance();

		foreach( $ct_ids as $ct_id ) {

			// Trash the template
			wp_trash_post( $ct_id );
			do_action( 'wpv_action_wpv_save_item', $ct_id );

			// Remove references to trashed template from Views options
			$settings->replace_abstract_ct_associations( $ct_id, 0, false );

		}

        $settings->save();

		$data['action'] = 'reload';
		wp_send_json_success( $data );

	} else {
        // One or more templates are in use, we need to show a confirmation.

		$exclude_loop_templates_ids = wpv_get_loop_content_template_ids();
		$excluded_ct_ids = $ct_ids;
		if ( count( $exclude_loop_templates_ids ) > 0 ) {
			$exclude_loop_templates_ids = array_map( 'esc_attr', $exclude_loop_templates_ids );
			$exclude_loop_templates_ids = array_map( 'trim', $exclude_loop_templates_ids );
			// is_numeric + intval does sanitization
			$exclude_loop_templates_ids = array_filter( $exclude_loop_templates_ids, 'is_numeric' );
			$exclude_loop_templates_ids = array_map( 'intval', $exclude_loop_templates_ids );
			if ( count( $exclude_loop_templates_ids ) > 0 ) {
				$excluded_ct_ids = array_merge( $excluded_ct_ids, $exclude_loop_templates_ids );
			}
		}

		// Get list of templates that can be used as a replacement for the trashed ones.
        $template_list = WPV_Content_Template_Embedded::query( array( 'post__not_in' => $excluded_ct_ids ) );

		// Render popup content.
		ob_start();

		?>
		<div class="wpv-dialog wpv-shortcode-gui-content-wrapper js-wpv-dialog-bulk-replace-content-template wpv-dialog-bulk-replace-content-template">
			<p><?php _e( 'These content templates are in use. What do you want to do?', 'wpv-views' ); ?></p>
			<?php
			// Show a div with options for each used template.
			foreach( $used_templates as $ct_id => $template_info ) {
				$template_title = $template_info['title'];
				$template_usage_count = $template_info['usage_count'];

				?>
				<div>
					<?php
						printf(
								'<p><strong>%s</strong> (%s %s)</p>',
								$template_title,
								__( 'used by', 'wpv-views' ),
								sprintf( _n( '1 item', '%s items', $template_usage_count, 'wpv-views' ), $template_usage_count ) );
					?>
					<ul>
						<?php
							/* Show an option to replace this template with another one, if there are some left.
							 * Radio buttons are grouped by name: "wpv-content-template-replace-{$template_id}-to".
							 * Select field for replacement template is identified as "wpv-ct-list-for-replace-{$template_id}"
							 *
							 * Submit button contains attributes 'data-ct-ids' with all Content Template IDs that should be trashed
							 * and 'data-replace-ids' with those that should be replaced. */
							if( !empty( $template_list ) ) {
								?>
								<li>
									<label>
										<?php
											printf(
													'<input type="radio" name="wpv-content-template-replace-%d-to"
														class="js-wpv-bulk-existing-posts-ct-replace-to js-wpv-bulk-existing-posts-ct-replace-to-selected-ct"
														value="different_template" id="wpv-content-template-replace-to" />',
													$ct_id );
											_e( 'Choose a different content template for them: ', 'wpv-views' );
										?>
									</label>
									<?php
										printf( '<select class="js-wpv-bulk-ct-list-for-replace" id="wpv-ct-list-for-replace-%d" data-template-id="%d">', esc_attr( $ct_id ), esc_attr( $ct_id ) );
										printf( '<option value="">%s</option>', __( 'Select Content Template', 'wpv-views' ) );
										foreach( $template_list as $temp_post ) {
											printf( '<option value="%s">%s</option>', $temp_post->ID, $temp_post->post_title );
										}
										printf( '</select>' );
									?>
								</li>
								<?php
							}
						?>
						<li>
							<label>
								<?php
									printf( '<input type="radio" name="wpv-content-template-replace-%d-to"
												class="js-wpv-bulk-existing-posts-ct-replace-to" value="no_template" />',
											$ct_id );
									_e( 'Don\'t use any content template for these items', 'wpv-views' );
								?>
							</label>
						</li>
					</ul>
				</div>
				<?php
				printf(
					'<input type="hidden" class="js-ct-bulk-replace-usage-data" data-ct-ids="%s" data-replace-ids="%s" />',
					urlencode( implode( ',', $ct_ids ) ),
					urlencode( implode( ',', array_keys( $used_templates ) ) )
				);
			}
			?>
		</div>
		<?php

		$dialog_content = ob_get_clean();;

		$data['action'] = 'dialog';
		$data['dialog_content'] = $dialog_content;
		wp_send_json_success( $data );
	}

}




// Change CT usage before move to trash
add_action('wp_ajax_wpv_ct_trash_with_replace', 'wpv_ct_trash_with_replace_callback');

/**
 * @deprecated Since 1.12 or earlier. Not used in Views.
 */
function wpv_ct_trash_with_replace_callback() {
	wpv_ajax_authenticate( 'wpv_view_listing_actions_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	if ( isset( $_POST['id'] ) ) {
		$ct_id = intval( $_POST['id'] );
	} else {
		$data = array(
			'type' => 'error',
			'message' => __( 'Invalid CT ID.', 'wpv-views' )
		);
		wp_send_json_error( $data );
		return; // PhpStorm, calm down!
	}

	global $wpdb;
	$replace = ( $_POST['replace_to'] == 0 ) ? intval( $_POST['replace_ct'] ) : 0;
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta}
			SET meta_value = %s
			WHERE meta_key = '_views_template'
			AND meta_value = %s",
			$replace,
			$ct_id
		)
	);

	$settings = WPV_Settings::get_instance();
	$settings->replace_abstract_ct_associations( $ct_id, $replace );

	$my_post = array(
		'ID'           => $ct_id,
		'post_status' => 'trash'
	);
	wp_update_post( $my_post );
	do_action( 'wpv_action_wpv_save_item', $ct_id );

	wp_send_json_success();
}



/**
 * Replace content templates that are being used by some posts and trash all given content templates (which may be a
 * superset of those being used).
 *
 * Expected POST parameters:
 * - ids: an array of IDs of all templates that should be trashed
 * - toreplace: dtto, templates that should be replaced
 * - replacements: dtto, replacement templates (same lenght and order as toreplace)
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 *
 * Content templates from 'toreplace' used in posts (and post types) will be replaced by 'replacements'. Zero values
 * in 'replacements' are interpreted as "no template". Then, all templates from 'ids' will be trashed.
 *
 * Outputs '1' on success.
 *
 * @since 1.7
 */
add_action( 'wp_ajax_wpv_ct_bulk_trash_with_replace', 'wpv_ct_bulk_trash_with_replace_callback' );

function wpv_ct_bulk_trash_with_replace_callback() {
	wpv_ajax_authenticate( 'wpv_view_listing_actions_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	if ( ! isset( $_POST['ids'] ) ) {
		// Don't allow empty input
		$data = array(
			'type' => 'error',
			'message' => __( 'Invalid CT ID.', 'wpv-views' )
		);
		wp_send_json_error( $data );
		return; // PhpStorm, calm down!
	} else if ( is_string( $_POST['ids'] ) ) {
		$ct_ids = array( $_POST['ids'] );
	} else {
		$ct_ids = $_POST['ids'];
	}

	$ct_ids = array_map( 'esc_attr', $ct_ids );
	$ct_ids = array_map( 'trim', $ct_ids );
	// is_numeric does sanitization
	$ct_ids = array_filter( $ct_ids, 'is_numeric' );
	$ct_ids = array_map( 'intval', $ct_ids );

	// This will hold template IDs as keys and IDs of their replacements as values. Value 0 indicates
	// "don't use any content template".
	$replacements = array();

	if (
		isset( $_POST['replacements'] )
		&& isset( $_POST['toreplace'] )
		&& is_array( $_POST['replacements'] )
		&& is_array( $_POST['toreplace'] )
	) {
		$sanitized_replacements = array_map( 'esc_attr', $_POST['replacements'] );
		$sanitized_replacements = array_map( 'trim', $sanitized_replacements );
		// is_numeric does sanitization
		$sanitized_replacements = array_filter( $sanitized_replacements, 'is_numeric' );
		$sanitized_replacements = array_map( 'intval', $sanitized_replacements );

		$sanitized_toreplace = array_map( 'esc_attr', $_POST['toreplace'] );
		$sanitized_toreplace = array_map( 'trim', $sanitized_toreplace );
		// is_numeric does sanitization
		$sanitized_toreplace = array_filter( $sanitized_toreplace, 'is_numeric' );
		$sanitized_toreplace = array_map( 'intval', $sanitized_toreplace );

		$replacement_count = count( $sanitized_replacements );
		$toreplace_count = count( $sanitized_toreplace );

		if ( $replacement_count == $toreplace_count ) {
			for ( $i = 0; $i < $replacement_count; ++$i ) {
				$replacements[ $sanitized_toreplace[ $i ] ] = $sanitized_replacements[ $i ];
			}
		} else {
			$data = array(
				'type' => 'error',
				'message' => __( 'Invalid data', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
	} else {
		$data = array(
			'type' => 'error',
			'message' => __( 'Invalid data', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}

	global $wpdb, $WPV_settings;

	// Replace content templates as requested
	foreach( $replacements as $original_template_id => $replacement_template_id ) {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_value = %s
				WHERE meta_key = '_views_template'
				AND meta_value = %s",
				$replacement_template_id,
				$original_template_id
			)
		);

		$WPV_settings->replace_abstract_ct_associations( $original_template_id, $replacement_template_id, false );

	}

	// Now trash all requested templates
	foreach( $ct_ids as $template_id ) {
		$my_post = array(
				'ID' => $template_id,
				'post_status' => 'trash' );
		wp_update_post( $my_post );
		do_action( 'wpv_action_wpv_save_item', $template_id );

		// Remove references to trashed template from Views options
		$WPV_settings->replace_abstract_ct_associations( $template_id, 0, false );
	}

	$WPV_settings->save();

	wp_send_json_success();
}



add_action( 'wp_ajax_wpv_ct_bulk_count_usage', 'wpv_ct_bulk_count_usage_callback' );

/**
 * Count posts where given Content Templates are used.
 *
 * For given array of Content Template IDs it calculates in how many posts is each template used.
 * Outputs a JSON representation of an array where keys are CT IDs and values are post counts.
 * This array also allways contains an element "0" with the sum of all post counts.
 *
 * Expected POST parameters:
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 * - ids: An array of CT IDs
 *
 * @since 1.7
 */
function wpv_ct_bulk_count_usage_callback() {
	wpv_ajax_authenticate( 'wpv_view_listing_actions_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	if( !isset( $_POST['ids'] ) ) {
		$ct_ids = array();
	} else if( is_string( $_POST['ids'] ) ) {
		$ct_ids = array( $_POST['ids'] );
	} else {
		$ct_ids = $_POST['ids'];
	}

	global $wpdb;
	$data = array();

	$ct_ids = array_map( 'esc_attr', $ct_ids );
	$ct_ids = array_map( 'trim', $ct_ids );
	// is_numeric does sanitization
	$ct_ids = array_filter( $ct_ids, 'is_numeric' );
	$ct_ids = array_map( 'intval', $ct_ids );

	$data['action'] = 'delete';

	if( sizeof( $ct_ids ) && $ct_ids[0] == -1 ) {
		$data['action'] = 'empty-trash';

        $ct_ids = $wpdb->get_results(
	        "SELECT ID as id
			FROM {$wpdb->posts}
			WHERE post_type = 'view-template'
			AND post_status = 'trash'"
        );
		$ct_ids = wp_list_pluck( $ct_ids, 'id' );
    }

	$data['ct_ids'] = $ct_ids;

	wp_send_json_success( $data );

}


/**
 * Bulk delete Content Templates.
 *
 * Expects following POST parameters:
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 * - ids: An array of CT IDs to be deleted.
 *
 * Deletes templates and removes all occurences of their IDs from Views options.
 *
 * Prints standard JSON success response.
 *
 * @since 1.7
 */
add_action( 'wp_ajax_wpv_ct_bulk_delete', 'wpv_ct_bulk_delete_callback' );

function wpv_ct_bulk_delete_callback() {
	wpv_ajax_authenticate( 'wpv_view_listing_actions_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

	if( !isset( $_POST['ids'] ) ) {
		$ct_ids = array();
	} else if( is_string( $_POST['ids'] ) ) {
		$ct_ids = array( $_POST['ids'] );
	} else {
		$ct_ids = $_POST['ids'];
	}

	$ct_ids = array_map( 'esc_attr', $ct_ids );
	$ct_ids = array_map( 'trim', $ct_ids );
	// is_numeric does sanitization
	$ct_ids = array_filter( $ct_ids, 'is_numeric' );
	$ct_ids = array_map( 'intval', $ct_ids );

    global $WPV_settings;

    foreach( $ct_ids as $ct_id ) {
		$WPV_settings->replace_abstract_ct_associations( $ct_id, 0, false );

		wp_delete_post( $ct_id );
	}

	$WPV_settings->save();

	wp_send_json_success();
}



add_action( 'wp_ajax_wpv_ct_bind_posts', 'wpv_ct_bind_posts_callback' );

/**
 * Bind specific posts to a Content Template.
 *
 * Following POST parameters are expected:
 * - id: Content Template ID
 * - wpnonce: A valid wpv_ct_{$id}_bind_posts_by_{$user_id} nonce.
 * - posts_to_bind: An array of post IDs that should be bound.
 *
 * Returns a default WP json response (error/success), possibly with a debug message
 * on error.
 *
 * @since 1.9
 */
function wpv_ct_bind_posts_callback() {
    // Authentication and validation
    if ( ! current_user_can( EDIT_VIEWS ) ) {
        wp_send_json_error( 'Untrusted user' );
    }
    $ct_id = (int) wpv_getpost( 'id' );
    $uid = get_current_user_id();

    $nonce_name = "wpv_ct_{$ct_id}_bind_posts_by_{$uid}";
    if ( ! wp_verify_nonce( wpv_getpost( 'wpnonce' ), $nonce_name ) ) {
        wp_send_json_error( "Security check ($nonce_name)" );
    }

    $ct = WPV_Content_Template::get_instance( $ct_id );
    if( null == $ct ) {
        wp_send_json_error( 'Invalid Content Template' );
    }

    $posts_to_bind = wpv_getpost( 'posts_to_bind' );
    if( !is_array( $posts_to_bind ) ) {
        wp_send_json_error( 'Invalid arguments (' . print_r( $posts_to_bind, true ) . ')' );
    }

    // Post binding
    $updated = $ct->bind_posts( $posts_to_bind );

    if( false === $updated ) {
        wp_send_json_error( 'bind_posts failed' );
    }

    wp_send_json_success( array( 'updated' => $updated ) );
}



/* ************************************************************************* *\
        Miscellaneous
\* ************************************************************************* */


/**
 * wpv_dismiss_pointer_callback
 *
 * Dismiss pointers created with Views, when needed
 *
 * @since 1.7
 *
 * @todo this needs a nonce, the earlier the better
 */
add_action( 'wp_ajax_wpv_dismiss_pointer', 'wpv_dismiss_pointer_callback' );

function wpv_dismiss_pointer_callback() {
	if ( ! isset( $_POST['name'] ) ) {
		echo 'wpv_failure';
		die();
	}
	$pointer = sanitize_key( $_POST['name'] );
	if ( empty( $pointer ) ) {
		echo 'wpv_failure';
		die();
	}
	wpv_dismiss_pointer( $pointer );
	echo 'wpv_success';
	die();
}



/* ************************************************************************* *\
        Helper functions
\* ************************************************************************* */


/**
 * Perform basic authentication check.
 *
 * Check user capability and nonce. Dies with an error message (wp_json_error() by default) if the authentization
 * is not successful.
 *
 * @param string $nonce_name Name of the nonce that should be verified.
 * @param array $args Arguments (
 *     @type string $nonce_parameter Name of the parameter containing nonce value.
 *         Optional, defaults to "wpnonce".
 *     @type string $parameter_source Determines where the function should look for the nonce parameter.
 *         Allowed values are 'get' and 'post'. Optional, defaults to 'post'.
 *     @type string $capability_needed Capability that user has to have in order to pass the check.
 *         Optional, default is EDIT_VIEWS.
 *     @type string $type_of_death How to indicate failure:
 *         - 'die': The error message will be echoed as a simple string. Default behaviour.
 *         - 'false': Do not die, just return false.
 *         - 'message': Call wp_json_error() and pass message as data.
 *         - 'data': Call wp_json_error with array( 'type' => 'capability'|'nonce', 'message' => $error_message )
 *         Optional, default is 'die'.
 *     )
 *
 * @return bool|void
 *
 * @since 1.9
 */
function wpv_ajax_authenticate( $nonce_name, $args = array() )
{
    // Read arguments
    $type_of_death = wpv_getarr( $args, 'type_of_death', 'die', array( 'die', 'false', 'message', 'data' ) );
    $nonce_parameter = wpv_getarr( $args, 'nonce_parameter', 'wpnonce' );
    $capability_needed = wpv_getarr( $args, 'capability_needed', EDIT_VIEWS );
    $parameter_source_name = wpv_getarr( $args, 'parameter_source', 'post', array( 'get', 'post' ) );
    $parameter_source = ( $parameter_source_name == 'get' ) ? $_GET : $_POST;

    $is_error = false;
    $error_message = null;
    $error_type = null;

    // Check permissions
    if ( ! current_user_can( $capability_needed ) ) {
        $error_message = __( 'You do not have permissions for that.', 'wpv-views' );
        $error_type = 'capability';
        $is_error = true;
    }

    // Check nonce
    if ( !$is_error && !wp_verify_nonce( wpv_getarr( $parameter_source, $nonce_parameter, '' ), $nonce_name ) ) {
        $error_message = __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' );
        $error_type = 'nonce';
        $is_error = true;
    }

    if( $is_error ) {
        switch( $type_of_death ) {

            case 'message':
                wp_send_json_error( $error_message );
                break;

            case 'data':
                wp_send_json_error( array( 'type' => $error_type, 'message' => $error_message ) );
                break;

            case 'false':
                return false;

            case 'die':
            default:
                die( $error_message );
                break;
        }
    }

    return true;
}
