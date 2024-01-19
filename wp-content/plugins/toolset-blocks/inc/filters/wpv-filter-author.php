<?php

/**
* Author filter
*
* @package Views
*
* @since unknown
*/

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Author_Filter::on_load();

/**
* WPV_Author_Filter
*
* Views Author Filter Class
*
* @since 1.7
* @since 1.12.1	Changes in the filter modes
* 		current_page becomes current_post_or_parent_post_view and tracks $WP_Views->get_current_page()
* 		top_current_post tracks $WP_Views->get_top_current_page()
* 		parent_view becomes parent_user_view
* @since 2.1	Added to WordPress Archives
* @since 2.1	Include this file only when editing a View or WordPress Archive, or when doing AJAX
*/

class WPV_Author_Filter {

    static function on_load() {
        add_action( 'init',			 array( 'WPV_Author_Filter', 'init' ) );
		add_action( 'admin_init',	 array( 'WPV_Author_Filter', 'admin_init' ) );
		add_action( 'rest_api_init', array( 'WPV_Author_Filter', 'admin_init' ) );
		// Scripts
		add_action( 'admin_enqueue_scripts', array( 'WPV_Author_Filter', 'admin_enqueue_scripts' ), 20 );
		// Custom search shortcode GUI
		//add_filter( 'wpv_filter_wpv_register_form_filters_shortcodes', array( 'WPV_Author_Filter', 'wpv_custom_search_filter_shortcodes_post_author' ) );
    }

    static function init() {
		wp_register_script(
			'views-filter-author-js',
			WPV_URL . "/res/js/filters/views_filter_author.js",
			array( 'suggest', 'underscore', 'views-filters-js' ),
			WPV_VERSION,
			false
		);
		$filter_author_translations = array(
			'ajaxurl'	=> wpv_get_views_ajaxurl(),
			'archive'	=> array(
							'disable_author_filter'	=> __( 'This filter will not be applied to Author archives', 'wpv-views' ),
						),
		);
		wp_localize_script( 'views-filter-author-js', 'wpv_filter_author_texts', $filter_author_translations );
    }

	static function admin_init() {
		// Register filter in dialogs
		add_filter( 'wpv_filters_add_filter',						array( 'WPV_Author_Filter', 'wpv_filters_add_filter_post_author' ), 1, 1 );
		add_filter( 'wpv_filters_add_archive_filter',				array( 'WPV_Author_Filter', 'wpv_filters_add_archive_filter_post_author' ), 1, 1 );
		// Register filter in lists
		add_action( 'wpv_add_filter_list_item',						array( 'WPV_Author_Filter', 'wpv_add_filter_post_author_list_item' ), 1, 1 );
		// Update and delete
		add_action( 'wp_ajax_wpv_filter_post_author_update',		array( 'WPV_Author_Filter', 'wpv_filter_post_author_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_post_author_delete',		array( 'WPV_Author_Filter', 'wpv_filter_post_author_delete_callback' ) );
		// Sugest
		add_action( 'wp_ajax_wpv_suggest_author',					array( 'WPV_Author_Filter', 'wpv_suggest_author' ) );
		add_action( 'wp_ajax_nopriv_wpv_suggest_author',			array( 'WPV_Author_Filter', 'wpv_suggest_author' ) );
		// TODO This might not be needed here, maybe for summary filter
		//add_action( 'wp_ajax_wpv_filter_author_sumary_update',	array( 'WPV_Author_Filter', 'wpv_filter_author_sumary_update_callback' ) );
	}

	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 1.7
	*/

	static function admin_enqueue_scripts( $hook ) {
		wp_enqueue_script( 'views-filter-author-js' );
	}

	/**
	* wpv_filters_add_filter_post_author
	*
	* Register the author filter in the popup dialog
	*
	* @param $filters
	*
	* @since unknown
	*/

	static function wpv_filters_add_filter_post_author( $filters ) {
		$filters['post_author'] = array(
			'name'		=> __( 'Post author', 'wpv-views' ),
			'present'	=> 'author_mode',
			'callback'	=> array( 'WPV_Author_Filter', 'wpv_add_new_filter_post_author_list_item' ),
			'group'		=> __( 'Post filters', 'wpv-views' )
		);
		return $filters;
	}

	/**
	* wpv_filters_add_archive_filter_post_author
	*
	* Register the author filter in the popup dialog
	*
	* @param $filters
	*
	* @since 2.1
	*/

	static function wpv_filters_add_archive_filter_post_author( $filters ) {
		$filters['post_author'] = array(
			'name'		=> __( 'Post author', 'wpv-views' ),
			'present'	=> 'author_mode',
			'callback'	=> array( 'WPV_Author_Filter', 'wpv_add_new_archive_filter_post_author_list_item' ),
			'group'		=> __( 'Post filters', 'wpv-views' )
		);
		return $filters;
	}

	/**
	* wpv_add_new_filter_post_author_list_item
	*
	* Register the author filter in the filters list
	*
	* @since unknown
	*/

	static function wpv_add_new_filter_post_author_list_item() {
		$args = array(
			'view-query-mode'	=> 'normal',
			'author_mode'		=> array( 'current_user' )
		);
		WPV_Author_Filter::wpv_add_filter_post_author_list_item( $args );
	}

	/**
	* wpv_add_new_archive_filter_post_author_list_item
	*
	* Register the author filter in the filters list
	*
	* @since 2.1
	*/

	static function wpv_add_new_archive_filter_post_author_list_item() {
		$args = array(
			'view-query-mode'	=> 'archive',
			'author_mode'		=> array( 'current_user' )
		);
		WPV_Author_Filter::wpv_add_filter_post_author_list_item( $args );
	}

	/**
	* wpv_add_filter_post_author_list_item
	*
	* Render author filter item in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_add_filter_post_author_list_item( $view_settings ) {
		if ( isset( $view_settings['author_mode'][0] ) ) {
			$li = WPV_Author_Filter::wpv_get_list_item_ui_post_author( $view_settings );
			WPV_Filter_Item::simple_filter_list_item( 'post_author', 'posts', 'post-author', __( 'Post author filter', 'wpv-views' ), $li );
		}
	}

	/**
	* wpv_get_list_item_ui_post_author
	*
	* Render author filter item content in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_get_list_item_ui_post_author( $view_settings = array() ) {
		if ( isset( $view_settings['author_mode'] ) && is_array( $view_settings['author_mode'] ) ) {
			$view_settings['author_mode'] = $view_settings['author_mode'][0];
		}
		ob_start();
		?>
		<p class='wpv-filter-post-author-edit-summary js-wpv-filter-summary js-wpv-filter-post-author-summary'>
			<?php echo wpv_get_filter_post_author_summary_txt( $view_settings ); ?>
		</p>
		<?php
		WPV_Filter_Item::simple_filter_list_item_buttons( 'post-author', 'wpv_filter_post_author_update', wp_create_nonce( 'wpv_view_filter_post_author_nonce' ), 'wpv_filter_post_author_delete', wp_create_nonce( 'wpv_view_filter_post_author_delete_nonce' ) );
		?>
		<div id="wpv-filter-post-author-edit" class="wpv-filter-edit js-wpv-filter-edit" style="padding-bottom:28px;">
			<div id="wpv-filter-post-author" class="js-wpv-filter-options js-wpv-filter-post-author-options">
				<?php WPV_Author_Filter::wpv_render_post_author_options( $view_settings ); ?>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
			<span class="filter-doc-help">
				<?php echo sprintf(__('%sLearn about filtering by Post Author%s', 'wpv-views'),
					'<a class="wpv-help-link" href="' . WPV_FILTER_BY_AUTHOR_LINK . '" target="_blank">',
					' &raquo;</a>'
				); ?>
			</span>
		</div>
		<?php
		$res = ob_get_clean();
		return $res;
	}

	/**
	* wpv_filter_post_author_update_callback
	*
	* Update author filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_post_author_update_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_post_author_nonce' )
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
		if ( empty( $_POST['filter_options'] ) ) {
			$data = array(
				'type' => 'data_missing',
				'message' => __( 'Wrong or missing data.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$view_id = intval( $_POST['id'] );
		parse_str( $_POST['filter_options'], $filter_author );
		$change = false;
		$view_array = get_post_meta( $view_id, '_wpv_settings', true );
		if (
			! isset( $filter_author['author_name'] )
			|| '' == $filter_author['author_name']
		) {
			$filter_author['author_name'] = '';
			$filter_author['author_id'] = 0;
		}
		$settings_to_check = array(
			'author_mode', 'author_name', 'author_id',
			'author_url_type', 'author_url', 'author_shortcode_type', 'author_shortcode', 'author_framework_type', 'author_framework'
		);
		foreach ( $settings_to_check as $set ) {
			if (
				isset( $filter_author[$set] )
				&& (
					! isset( $view_array[$set] )
					|| $filter_author[$set] != $view_array[$set]
				)
			) {
				if ( is_array( $filter_author[$set] ) ) {
					$filter_author[$set] = array_map( 'sanitize_text_field', $filter_author[$set] );
				} else {
					$filter_author[$set] = sanitize_text_field( $filter_author[$set] );
				}
				$change = true;
				$view_array[$set] = $filter_author[$set];
			}
		}
		if ( $change ) {
			$result = update_post_meta( $view_id, '_wpv_settings', $view_array );
			do_action( 'wpv_action_wpv_save_item', $view_id );
		}
		$filter_author['author_mode'] = $filter_author['author_mode'][0];
		$data = array(
			'id' => $view_id,
			'message' => __( 'Post author filter saved', 'wpv-views' ),
			'summary' => wpv_get_filter_post_author_summary_txt( $filter_author )
		);
		wp_send_json_success( $data );
	}

	/**
	* Update author filter summary callback
	*/
	/*
	static function wpv_filter_author_sumary_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_author_nonce' ) ) {
			die( "Security check" );
		}
		parse_str( $_POST['filter_author'], $filter_author );
		$filter_author['author_mode'] = $filter_author['author_mode'][0];
		echo wpv_get_filter_post_author_summary_txt( $filter_author );
		die();
	}
	*/

	/**
	* wpv_filter_post_author_delete_callback
	*
	* Delete author filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_post_author_delete_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_post_author_delete_nonce' )
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
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		$settings_to_check = array(
			'author_mode', 'author_name', 'author_id',
			'author_url_type', 'author_url', 'author_shortcode_type', 'author_shortcode', 'author_framework_type', 'author_framework'
		);
		foreach ( $settings_to_check as $set ) {
			if ( isset( $view_array[$set] ) ) {
				unset( $view_array[$set] );
			}
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
		$data = array(
			'id' => $_POST["id"],
			'message' => __( 'Post author filter deleted', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

	/**
	* wpv_suggest_author
	*
	* Suggest authors using an AJAX callback and built-in suggest script
	*
	* @since unknown
	*/

	static function wpv_suggest_author() {
		global $wpdb;
		$user = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT ID, display_name FROM {$wpdb->users}
				INNER JOIN {$wpdb->usermeta}
				WHERE display_name LIKE %s
				ORDER BY display_name
				LIMIT 0, 20",
				$user
			)
		);
		foreach ( $results as $row ) {
			echo $row->display_name . ' # userID: ' . $row->ID . "\n";
		}
		die();
	}

	/**
	* get_options_by_query_mode
	*
	* Define which options will be offered depending on the query mode.
	*
	* @param $query_mode	string	'normal'|'archive'|?
	*
	* @since 2.1
	*/

	static function get_options_by_query_mode( $query_mode = 'normal' ) {
		$options = array();
		if ( 'normal' == $query_mode ) {
			$options = array( 'current_user', 'top_current_post', 'current_post_or_parent_post_view', 'parent_user_view', 'this_user', 'by_url', 'shortcode', 'framework' );
		} else {
			$options = array( 'current_user', 'this_user', 'by_url', 'framework' );
		}
		return $options;
	}

	/**
	* render_options_by_author_mode
	*
	* Render each filter option.
	*
	* @param $author_mode	string	'current_user'|'top_current_post'|'current_post_or_parent_post_view'|'parent_user_view'|'this_user'|'by_url'|'shortcode'|'framework'
	* @param @view_settings	array	The View settings.
	*
	* @since 2.1
	*/

	static function render_options_by_author_mode( $author_mode, $view_settings ) {
		switch ( $author_mode ) {
			case 'current_user':
				?>
				<li>
					<input type="radio" id="wpv-filter-author-current-user" name="author_mode[]" value="current_user" <?php checked( $view_settings['author_mode'], 'current_user' ); ?> autocomplete="off" />
					<label for="wpv-filter-author-current-user"><?php _e('Post author is the same as the logged in user', 'wpv-views'); ?></label>
				</li>
				<?php
				break;
			case 'top_current_post':
				?>
				<li>
					<input type="radio" id="wpv-filter-author-top-current-post" name="author_mode[]" value="top_current_post" <?php checked( $view_settings['author_mode'], 'top_current_post' ); ?> autocomplete="off" />
					<label for="wpv-filter-author-top-current-post"><?php _e('Post author is the author of the page where this View is shown', 'wpv-views'); ?></label>
				</li>
				<?php
				break;
			case 'current_post_or_parent_post_view':
				?>
				<li>
					<input type="radio" id="wpv-filter-author-current-post" name="author_mode[]" value="current_post_or_parent_post_view" <?php checked( in_array( $view_settings['author_mode'], array( 'current_page', 'current_post_or_parent_post_view' ) ) ); ?> autocomplete="off" />
					<label for="wpv-filter-author-current-post"><?php _e('Post author is the author of the current post in the loop', 'wpv-views'); ?></label>
				</li>
				<?php
				break;
			case 'parent_user_view':
				?>
				<li>
					<input type="radio" id="wpv-filter-author-parent-view" name="author_mode[]" value="parent_user_view" <?php checked( in_array( $view_settings['author_mode'], array( 'parent_view', 'parent_user_view' ) ) ); ?> autocomplete="off" />
					<label for="wpv-filter-author-parent-view"><?php _e('Post author is set by the parent User View', 'wpv-views'); ?></label>
				</li>
				<?php
				break;
			case 'this_user':
				?>
				<li>
					<input type="radio" id="wpv-filter-author-this-user" name="author_mode[]" value="this_user" <?php checked( $view_settings['author_mode'], 'this_user' ); ?> autocomplete="off" />
					<label for="wpv-filter-author-this-user"><?php _e('Post author is ', 'wpv-views'); ?></label>
					<?php
					$author_display_name = $view_settings['author_name'];
					if (
						0 != $view_settings['author_id']
						&& '' == $author_display_name
						&& is_numeric( $view_settings['author_id'] )
					) {
						$user_info = get_userdata( intval( $view_settings['author_id'] ) );
						if ( $user_info ) {
							$author_display_name = $user_info->display_name;
						} else {
							$view_settings['author_id'] = 0;
							$author_display_name= '';
						}
					} else {
						$view_settings['author_id'] = 0;
						$author_display_name= '';
					}
					?>
					<input id="wpv_author_name" class="author_suggest js-author-suggest" type='text' name="author_name" value="<?php echo esc_attr( $author_display_name ); ?>" size="15" placeholder="<?php echo esc_attr( __( 'Start typing', 'wpv-views' ) ); ?>" />
					<input id="wpv_author" class="author_suggest_id js-author-suggest-id" type='hidden' name="author_id" value="<?php echo esc_attr( $view_settings['author_id'] ); ?>" />
				</li>
				<?php
				break;
			case 'by_url':
				?>
				<li>
					<input type="radio" id="wpv-filter-author-by-url" name="author_mode[]" value="by_url" <?php checked( $view_settings['author_mode'], 'by_url' ); ?> autocomplete="off" />
					<label for="wpv-filter-author-by-url"><?php _e('Author\'s ', 'wpv-views'); ?></label>
					<select id="wpv_author_url_type" name="author_url_type" autocomplete="off">
						<option value="id" <?php selected( $view_settings['author_url_type'], 'id' ); ?>><?php _e( 'ID', 'wpv-views' ); ?></option>
						<option value="username" <?php selected( $view_settings['author_url_type'], 'username' ); ?>><?php _e( 'username', 'wpv-views' ); ?></option>
					</select>
					<label for="wpv-author-url"><?php _e(' is set by the URL parameter: ', 'wpv-views'); ?></label>
					<input id="wpv-author-url" type='text' class="js-wpv-filter-author-url js-wpv-filter-validate" data-type="url" data-class="js-wpv-filter-author-url" name="author_url" value="<?php echo esc_attr( $view_settings['author_url'] ); ?>" size="10" autocomplete="off" />
				</li>
				<?php
				break;
			case 'shortcode':
				?>
				<li>
					<input type="radio" id="wpv-filter-author-shortcode" name="author_mode[]" value="shortcode" <?php checked( $view_settings['author_mode'], 'shortcode' ); ?> autocomplete="off" />
					<label for="wpv-filter-author-shortcode"><?php _e('Author\'s ', 'wpv-views'); ?></label>
					<select id="wpv_author_shortcode_type" name="author_shortcode_type" autocomplete="off">
						<option value="id" <?php selected( $view_settings['author_shortcode_type'], 'id' ); ?>><?php _e( 'ID', 'wpv-views' ); ?></option>
						<option value="username" <?php selected( $view_settings['author_shortcode_type'], 'username' ); ?>><?php _e( 'username', 'wpv-views' ); ?></option>
					</select>
					<label for="wpv-author-shortcode"><?php _e(' is set by the View shortcode attribute: ', 'wpv-views'); ?></label>
					<input id="wpv-author-shortcode" type='text' class="js-wpv-filter-author-shortcode js-wpv-filter-validate" data-type="shortcode" data-class="js-wpv-filter-author-shortcode" name="author_shortcode" value="<?php echo esc_attr( $view_settings['author_shortcode'] ); ?>" size="10" autocomplete="off" />
				</li>
				<?php
				break;
			case 'framework':
				global $WP_Views_fapi;
				if ( $WP_Views_fapi->framework_valid ) {
					$framework_data = $WP_Views_fapi->framework_data
				?>
				<li>
					<input type="radio" id="wpv-filter-author-framework" name="author_mode[]" value="framework" <?php checked( $view_settings['author_mode'], 'framework' ); ?> autocomplete="off" />
					<label for="wpv-filter-author-framework"><?php _e('Author\'s ', 'wpv-views'); ?></label>
					<select id="wpv_author_framework_type" name="author_framework_type" autocomplete="off">
						<option value="id" <?php selected( $view_settings['author_framework_type'], 'id' ); ?>><?php _e( 'ID', 'wpv-views' ); ?></option>
						<option value="username" <?php selected( $view_settings['author_framework_type'], 'username' ); ?>><?php _e( 'username', 'wpv-views' ); ?></option>
					</select>
					<label for="wpv-author-framework"><?php echo sprintf( __( ' is set by the %s key: ', 'wpv-views' ), sanitize_text_field( $framework_data['name'] ) ); ?></label>
					<select name="author_framework" autocomplete="off">
						<option value=""><?php _e( 'Select a key', 'wpv-views' ); ?></option>
						<?php
						$fw_key_options = array();
						$fw_key_options = apply_filters( 'wpv_filter_extend_framework_options_for_post_author', $fw_key_options );
						foreach ( $fw_key_options as $index => $value ) {
							?>
							<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['author_framework'], $index ); ?>><?php echo $value; ?></option>
							<?php
						}
						?>
					</select>
				</li>
				<?php
				}
				break;
		};
	}

	/**
	* wpv_render_post_author_options
	*
	* Render author filter options
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_render_post_author_options( $view_settings = array() ) {
		$defaults = array(
			'view-query-mode'		=> 'normal',
			'author_mode'			=> 'current_user',
			'author_name'			=> '',
			'author_id'				=> 0,
			'author_url'			=> 'author-filter',
			'author_url_type'		=> '',
			'author_shortcode'		=> 'author',
			'author_shortcode_type'	=> '',
			'author_framework'		=> '',
			'author_framework_type'	=> ''
		);
		$view_settings = wp_parse_args( $view_settings, $defaults );
		?>
		<h4><?php _e( 'How to filter', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set">
			<?php
			$options_to_render = WPV_Author_Filter::get_options_by_query_mode( $view_settings['view-query-mode'] );
			foreach ( $options_to_render as $renderer ) {
				WPV_Author_Filter::render_options_by_author_mode( $renderer, $view_settings );
			}
			?>
		</ul>
		<div class="filter-helper js-wpv-author-helper"></div>
		<?php
	}

	/**
	 * Register the query filter by post author in the filters GUI.
	 *
	 * @since WIP 2.4.0
	 */

	static function wpv_custom_search_filter_shortcodes_post_author( $form_filters_shortcodes ) {
		$form_filters_shortcodes['wpv-control-post-author'] = array(
			'query_type_target'				=> 'posts',
			'custom_search_filter_group'	=> __( 'Post filters', 'wpv-views' ),
			'custom_search_filter_items'	=> array(
												'post_author'	=> array(
													'name'			=> __( 'Post author', 'wpv-views' ),
													'present'		=> 'author_mode',
													'params'		=> array()
												)
			)
		);
		return $form_filters_shortcodes;
	}

}
