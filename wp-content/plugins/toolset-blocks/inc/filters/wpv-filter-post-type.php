<?php

/**
 * Post type filter
 *
 * @package Views
 *
 * @since 2.4.0
 *
 * @note WIP
 */

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Post_Type_Filter::on_load();

/**
 * Views Post Type Filter Class
 *
 * @data Schema:
 * 	$view_settings
 * 		post_type_filter
 * 			mode		string	'url'|'shortcode'|'framework'
 *			url'		string	'post-type-filter'
 *			shortcode'	string	'posttype'
 *			framework'	string	''
 *
 * @since 2.4.0
 */

class WPV_Post_Type_Filter {

    static function on_load() {
        //add_action( 'init',			array( 'WPV_Post_Type_Filter', 'init' ) );
		//add_action( 'admin_init',	array( 'WPV_Post_Type_Filter', 'admin_init' ) );
		// Scripts
		//add_action( 'admin_enqueue_scripts', array( 'WPV_Post_Type_Filter', 'admin_enqueue_scripts' ), 20 );
		// Custom search shortcode GUI
		//add_filter( 'wpv_filter_wpv_register_form_filters_shortcodes', array( 'WPV_Post_Type_Filter', 'wpv_custom_search_filter_shortcodes_post_type' ) );
    }

    static function init() {
		wp_register_script( 'views-filter-post-type-js', ( WPV_URL . "/res/js/filters/views_filter_post_type.js" ), array( 'underscore', 'views-filters-js' ), WPV_VERSION, true );
		$filter_post_type_translations = array(
			'ajaxurl'	=> wpv_get_views_ajaxurl(),
		);
		wp_localize_script( 'views-filter-post-type-js', 'wpv_filter_post_type_texts', $filter_post_type_translations );
    }

	static function admin_init() {
		// Register filter in dialogs
		add_filter( 'wpv_filters_add_filter',							array( 'WPV_Post_Type_Filter', 'wpv_filters_add_filter_post_type' ), 1, 1 );
		//add_filter( 'wpv_filters_add_archive_filter',					array( 'WPV_Post_Type_Filter', 'wpv_filters_add_archive_filter_post_type' ), 1, 1 );
		// Register filter in lists
		add_action( 'wpv_add_filter_list_item',							array( 'WPV_Post_Type_Filter', 'wpv_add_filter_post_type_list_item' ), 1, 1 );
		// Update and delete
		add_action( 'wp_ajax_wpv_filter_post_type_update',				array( 'WPV_Post_Type_Filter', 'wpv_filter_post_type_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_post_type_delete',				array( 'WPV_Post_Type_Filter', 'wpv_filter_post_type_delete_callback' ) );
	}

	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 2.4.0
	*/

	static function admin_enqueue_scripts( $hook ) {
		wp_enqueue_script( 'views-filter-post-type-js' );
	}

	/**
	* wpv_filters_add_filter_post_type
	*
	* Register the post type filter in the popup dialog
	*
	* @param $filters
	*
	* @since 2.4.0
	*/

	static function wpv_filters_add_filter_post_type( $filters ) {
		$filters['post_type'] = array(
			'name'		=> __( 'Post type', 'wpv-views' ),
			'present'	=> 'post_type_filter',
			'callback'	=> array( 'WPV_Post_Type_Filter', 'wpv_add_new_filter_post_type_list_item' ),
			'group'		=> __( 'Post filters', 'wpv-views' )
		);
		return $filters;
	}

	/**
	* wpv_add_new_filter_post_type_list_item
	*
	* Register the post type filter in the filters list
	*
	* @since 2.4.0
	*/

	static function wpv_add_new_filter_post_type_list_item() {
		$args = array(
			'view-query-mode'	=> 'normal',
			'post_type_filter'	=> array(
				'mode'		=> 'url',
				'url'		=> 'post-type-filter',
				'shortcode'	=> 'posttype',
				'framework'	=> ''
			)
		);
		WPV_Post_Type_Filter::wpv_add_filter_post_type_list_item( $args );
	}

	/**
	* wpv_add_filter_post_type_list_item
	*
	* Render post type filter item in the filters list
	*
	* @param $view_settings
	*
	* @since 2.4.0
	*/

	static function wpv_add_filter_post_type_list_item( $view_settings ) {
		if ( isset( $view_settings['post_type_filter'] ) ) {
			$li = WPV_Post_Type_Filter::wpv_get_list_item_ui_post_type( $view_settings );
			WPV_Filter_Item::simple_filter_list_item( 'post_type', 'posts', 'post-type', __( 'Post type filter', 'wpv-views' ), $li );
		}
	}

	/**
	* wpv_get_list_item_ui_post_type
	*
	* Render post type filter item content in the filters list
	*
	* @param $view_settings
	*
	* @since 2.4.0
	*/

	static function wpv_get_list_item_ui_post_type( $view_settings = array() ) {
		ob_start();
		?>
		<p class='wpv-filter-post-type-edit-summary js-wpv-filter-summary js-wpv-filter-post-type-summary'>
			<?php echo wpv_get_filter_post_type_summary_txt( $view_settings ); ?>
		</p>
		<?php
		WPV_Filter_Item::simple_filter_list_item_buttons( 'post-type', 'wpv_filter_post_type_update', wp_create_nonce( 'wpv_view_filter_post_type_nonce' ), 'wpv_filter_post_type_delete', wp_create_nonce( 'wpv_view_filter_post_type_delete_nonce' ) );
		?>
		<div id="wpv-filter-post-type-edit" class="wpv-filter-edit js-wpv-filter-edit" style="padding-bottom:28px;">
			<div id="wpv-filter-post-type" class="js-wpv-filter-options js-wpv-filter-post-type-options">
				<?php WPV_Post_Type_Filter::wpv_render_post_type_options( $view_settings ); ?>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
			<span class="filter-doc-help">
				<?php echo sprintf(__('%sLearn about filtering by Post Types%s', 'wpv-views'),
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
	* wpv_filter_post_type_update_callback
	*
	* Update post type filter callback
	*
	* @since 2.4.0
	*/

	static function wpv_filter_post_type_update_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_post_type_nonce' )
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

		parse_str( $_POST['filter_options'], $filter_options );

		if (
			! isset( $filter_options['post_type_filter'] )
			|| ! is_array( $filter_options['post_type_filter'] )
		) {
			$data = array(
				'type' => 'data_missing',
				'message' => __( 'Wrong or missing data.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		$change = false;

		$view_array = get_post_meta( $view_id, '_wpv_settings', true );

		$filter_options['post_type_filter']	= array_map( 'sanitize_text_field', $filter_options['post_type_filter'] );
		$defaults = array(
			'mode'		=> '',
			'url'		=> '',
			'shortcode'	=> '',
			'framework'	=> ''
		);
		$filter_options['post_type_filter']	= wp_parse_args( $filter_options['post_type_filter'], $defaults );
		foreach ( $filter_options['post_type_filter'] as $key => $value ) {
			if (
				! isset( $view_array['post_type_filter'][ $key ] )
				|| $view_array['post_type_filter'][ $key ] != $value
			) {
				$view_array['post_type_filter'][ $key ] = $value;
				$change = true;
			}
		}

		if ( $change ) {
			$result = update_post_meta( $view_id, '_wpv_settings', $view_array );
			do_action( 'wpv_action_wpv_save_item', $view_id );
		}

		$data = array(
			'id' => $view_id,
			'message' => __( 'Post type filter saved', 'wpv-views' ),
			'summary' => wpv_get_filter_post_type_summary_txt( $filter_options )
		);
		wp_send_json_success( $data );
	}

	/**
	* wpv_filter_post_type_delete_callback
	*
	* Delete post type filter callback
	*
	* @since 2.4.0
	*/

	static function wpv_filter_post_type_delete_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_post_type_delete_nonce' )
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

		if ( isset( $view_array['post_type_filter'] ) ) {
			unset( $view_array['post_type_filter'] );
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );

		do_action( 'wpv_action_wpv_save_item', $_POST["id"] );

		$data = array(
			'id'		=> $_POST["id"],
			'message'	=> __( 'Post type filter deleted', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

	/**
	* get_options_by_query_mode
	*
	* Define which options will be offered depending on the query mode.
	*
	* @param $query_mode	string	'normal'|'archive'|?
	*
	* @since 2.4.0
	*/

	static function get_options_by_query_mode( $query_mode = 'normal' ) {
		$options = array();
		if ( 'normal' == $query_mode ) {
			$options = array( 'url', 'shortcode', 'framework' );
		} else {
			$options = array( 'url', 'framework' );
		}
		return $options;
	}

	/**
	* render_options_by_post_type_mode
	*
	* Render each filter option.
	*
	* @param $post_type_mode	string	'url'|'shortcode'|'framework'
	* @param @view_settings		array	The View settings.
	*
	* @since 2.4.0
	*/

	static function render_options_by_post_type_mode( $post_type_mode, $view_settings ) {
		switch ( $post_type_mode ) {
			case 'url':
				?>
				<li>
					<input type="radio" id="wpv-filter-post-type-by-url" name="post_type_filter[mode]" value="url" <?php checked( $view_settings['post_type_filter']['mode'], 'url' ); ?> autocomplete="off" />
					<label for="wpv-filter-post-type-by-url"><?php _e('Post type slug is set by the URL parameter:', 'wpv-views'); ?></label>
					<input id="wpv-post-type-url" type='text' class="js-wpv-filter-post-type-url js-wpv-filter-validate" data-type="url" data-class="js-wpv-filter-post-type-url" name="post_type_filter[url]" value="<?php echo esc_attr( $view_settings['post_type_filter']['url'] ); ?>" size="10" autocomplete="off" />
				</li>
				<?php
				break;
			case 'shortcode':
				?>
				<li>
					<input type="radio" id="wpv-filter-post-type-shortcode" name="post_type_filter[mode]" value="shortcode" <?php checked( $view_settings['post_type_filter']['mode'], 'shortcode' ); ?> autocomplete="off" />
					<label for="wpv-filter-post-type-shortcode"><?php _e('Post type slug is set by the View shortcode attribute: ', 'wpv-views'); ?></label>
					<input id="wpv-post-type-shortcode" type='text' class="js-wpv-filter-post-type-shortcode js-wpv-filter-validate" data-type="shortcode" data-class="js-wpv-filter-post-type-shortcode" name="post_type_filter[shortcode]" value="<?php echo esc_attr( $view_settings['post_type_filter']['shortcode'] ); ?>" size="10" autocomplete="off" />
				</li>
				<?php
				break;
			case 'framework':
				global $WP_Views_fapi;
				if ( $WP_Views_fapi->framework_valid ) {
					$framework_data = $WP_Views_fapi->framework_data
				?>
				<li>
					<input type="radio" id="wpv-filter-post-type-framework" name="post_type_filter[mode]" value="framework" <?php checked( $view_settings['post_type_filter']['mode'], 'framework' ); ?> autocomplete="off" />
					<label for="wpv-filter-post-type-framework"><?php echo sprintf( __( 'Post type slug is set by the %s key: ', 'wpv-views' ), sanitize_text_field( $framework_data['name'] ) ); ?></label>
					<select name="post_type_filter[framework]" autocomplete="off">
						<option value=""><?php _e( 'Select a key', 'wpv-views' ); ?></option>
						<?php
						$fw_key_options = array();
						$fw_key_options = apply_filters( 'wpv_filter_extend_framework_options_for_post_type', $fw_key_options );
						foreach ( $fw_key_options as $index => $value ) {
							?>
							<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['post_type_filter']['framework'], $index ); ?>><?php echo $value; ?></option>
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
	* wpv_render_post_type_options
	*
	* Render post type filter options
	*
	* @param $view_settings
	*
	* @since 2.4.0
	*/

	static function wpv_render_post_type_options( $view_settings = array() ) {
		$defaults = array(
			'mode'		=> 'url',
			'url'		=> 'post-type-filter',
			'shortcode'	=> 'posttype',
			'framework'	=> ''
		);
		$view_settings['view-query-mode']	= isset( $view_settings['view-query-mode'] ) ? $view_settings['view-query-mode'] : 'normal';
		$view_settings['post_type_filter']	= ( isset( $view_settings['post_type_filter'] ) && is_array( $view_settings['post_type_filter'] ) ) ? $view_settings['post_type_filter'] : array();
		$view_settings['post_type_filter']	= wp_parse_args( $view_settings['post_type_filter'], $defaults );
		?>
		<h4><?php _e( 'How to filter', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set">
			<?php
			$options_to_render = WPV_Post_Type_Filter::get_options_by_query_mode( $view_settings['view-query-mode'] );
			foreach ( $options_to_render as $renderer ) {
				WPV_Post_Type_Filter::render_options_by_post_type_mode( $renderer, $view_settings );
			}
			?>
		</ul>
		<div class="filter-helper js-wpv-post-type-helper"></div>
		<?php
	}

	/**
	 * Register the wpv-control-post-type shortcode filter.
	 *
	 * @since 2.4.0
	 */

	static function wpv_custom_search_filter_shortcodes_post_type( $shortcodes ) {
		$shortcodes['wpv-control-post-type'] = array(
			'query_type_target'				=> 'posts',
			'custom_search_filter_group'	=> __( 'Post filters', 'wpv-views' ),
			'custom_search_filter_items'	=> array(
												'post_type'	=> array(
													'name'			=> __( 'Post type', 'wpv-views' ),
													'present'		=> 'post_type_filter',
													'params'		=> array()
												)
			)
		);
		return $shortcodes;
	}

}
