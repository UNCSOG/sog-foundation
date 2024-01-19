<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Editor_Query_Filter::on_load();

class WPV_Editor_Query_Filter {

	static function on_load() {
		// Register the section in the screen options of the editor pages
		add_filter( 'wpv_screen_options_editor_section_query',		array( 'WPV_Editor_Query_Filter', 'wpv_screen_options_query_filter' ), 50 );
		add_filter( 'wpv_screen_options_wpa_editor_section_query',	array( 'WPV_Editor_Query_Filter', 'wpv_screen_options_query_filter' ), 50 );
		// Register the section in the editor pages
		add_action( 'wpv_action_view_editor_section_query',			array( 'WPV_Editor_Query_Filter', 'wpv_editor_section_query_filter' ), 50, 2 );
		add_action( 'wpv_action_wpa_editor_section_query',			array( 'WPV_Editor_Query_Filter', 'wpv_editor_section_query_filter' ), 50, 2 );
		// AJAX management

	}

	static function wpv_screen_options_query_filter( $sections ) {
		$sections['content-filter'] = array(
			'name'		=> __( 'Query Filter', 'wpv-views' ),
			'disabled'	=> false,
		);
		return $sections;
	}



	static function  wpv_editor_section_query_filter( $view_settings, $view_id, $display_header = true ) {
		$hide = '';
		if (
			isset( $view_settings['sections-show-hide'] )
			&& isset( $view_settings['sections-show-hide']['content-filter'] )
			&& 'off' == $view_settings['sections-show-hide']['content-filter']
		) {
			$hide = ' hidden';
		}
		if (
			isset( $view_settings['view-query-mode'] )
			&& $view_settings['view-query-mode'] == 'normal'
		) {
			$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'filter_the_results' );
		} else if ( isset( $view_settings['query_type'][0] ) ) {
			$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'filter_the_archive_results' );
		}
		?>
		<div class="wpv-setting-container wpv-settings-content-filter js-wpv-settings-content-filter<?php echo $hide; ?>">
			<?php if ( $display_header ) { ?>
				<div class="wpv-settings-header">
					<h2>
						<?php _e( 'Query Filter', 'wpv-views' ) ?>
						<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
							data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
							data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
						</i>
					</h2>
				</div>
			<?php } ?>
			<div class="wpv-setting">
				<p class="js-no-filters hidden"><?php _e( 'No filters set', 'wpv-views' ) ?></p>
				<ul class="filter-list js-filter-list hidden">
					<?php
					if (
						isset( $view_settings['query_type'] )
						&& isset( $view_settings['query_type'][0] )
					) {
						wpv_display_filters_list( $view_settings );
					}
					?>
				</ul>
				<input type="hidden" class="js-wpv-filter-update-filters-list-nonce" value="<?php echo wp_create_nonce( 'wpv_view_filter_update_filters_list_nonce' ); ?>" />
				<p>
					<button class="button-secondary js-wpv-filter-add-filter" type="button" data-empty="<?php echo esc_attr( __('Add New Filter', 'wpv-views') ); ?>" data-nonempty="<?php echo esc_attr( __('Add another filter', 'wpv-views') ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_add_filter' ); ?>">
						<i class="icon-plus fa fa-plus"></i> <?php echo esc_attr( __('Add a filter', 'wpv-views') ); ?>
					</button>
				</p>
			</div>
		</div>
	<?php
	}

	/**
	 * Check whether a give filter is already living in the settings.
	 *
	 * @param mixed[] $view_settings
	 * @param string|string[] $canary Flag to check and decide whether the filter already exists.
	 * @return bool
	 */
	public static function is_filter_in_use( $view_settings, $canary ) {
		if ( is_string( $canary ) ) {
			return ( false !== toolset_getarr( $view_settings, $canary, false ) );
		}

		if ( is_array( $canary ) ) {
			return ( false !== toolset_getnest( $view_settings, $canary, false ) );
		}

		return false;
	}

}

add_action('admin_head', 'wpv_filter_url_check_js');

function wpv_filter_url_check_js() {
	// TODO move this to the script localization
	$reserved_list = array(
		'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category', 'category__and', 'category__in',
		'category__not_in', 'category_name', 'comments_per_page', 'comments_popup', 'customize_messenger_channel',
		'customized', 'cpage', 'day', 'debug', 'error', 'exact', 'feed', 'hour', 'link_category', 'm', 'minute',
		'monthnum', 'more', 'name', 'nav_menu', 'nonce', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id',
		'paged', 'pagename', 'pb', 'perm', 'post', 'post__in', 'post__not_in', 'post_format', 'post_mime_type', 'post_status',
		'post_tag', 'post_type', 'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search',
		'second', 'sentence', 'showposts', 'static', 'subpost', 'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in',
		'tag_id', 'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'theme', 'type', 'w', 'withcomments', 'withoutcomments',
		'year'
	);

	$toolset_reserved_words = array(
		'wpv_column_sort_id', 'wpv_column_sort_dir',
		'wpv_sort_orderby', 'wpv_sort_order', 'wpv_sort_orderby_as', 'wpv_sort_orderby_second', 'wpv_sort_order_second',
		'wpv_paged', 'wpv_paged_preload_reach',
		'wpv_view_count', 'wpv_filter_submit',
		'wpv_post_search', 'wpv_taxonomy_search',
		'wpv_error'
	);
	$toolset_reserved_words = apply_filters('wpv_toolset_reserved_words', $toolset_reserved_words);

	$toolset_reserved_attributes = array(
		'name', 'post_type', 'order', 'orderby', 'orderby_as', 'order_second', 'orderby_second', 'limit', 'offset', 'posts_per_page', 'cached'
	);
	$toolset_reserved_attributes = apply_filters('wpv_toolset_reserved_attributes', $toolset_reserved_attributes);

	global $wp_post_types;
    	$reserved_post_types = array_keys( $wp_post_types );

    	$wpv_taxes = get_taxonomies();
    	$reserved_taxonomies = array_keys( $wpv_taxes );

    	$wpv_forbidden_parameters = array(
		'wordpress' => $reserved_list,
		'toolset' => $toolset_reserved_words,
		'toolset_attr' => $toolset_reserved_attributes,
		'post_type' => $reserved_post_types,
		'taxonomy' => $reserved_taxonomies,
    	);

    	$hierarchical_post_names = array();
    	$hierarchical_post_types = get_post_types( array( 'hierarchical' => true ), 'objects');
    	foreach ($hierarchical_post_types as $post_type) {
		$hierarchical_post_names[] = $post_type->name;
    	}

	?>
    <script type="text/javascript">
		var wpv_forbidden_parameters = <?php echo json_encode($wpv_forbidden_parameters); ?>;
		var wpv_hierarchical_post_types = <?php echo json_encode($hierarchical_post_names); ?>;
	</script>
	<?php
}

// @todo add proper wp_send_json_error/wp_send_json_success management here

add_action( 'wp_ajax_wpv_filters_add_filter_row', 'wpv_filters_add_filter_row_callback' );

function wpv_filters_add_filter_row_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		die( "Security check" );
	}
	$nonce = $_POST["wpnonce"];
	if ( ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filters_add_filter_nonce' ) ) {
		die( "Security check" );
	}
	if (
		! isset( $_POST["id"] )
		|| ! is_numeric( $_POST["id"] )
		|| intval( $_POST['id'] ) < 1
	) {
		die( "Security check" );
	}
	if ( empty( $_POST['filter_type'] ) ) {
		die( "Unexpected filter type" );
	}
	$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
	if (
		! isset( $view_array['taxonomy_type'] )
		|| empty( $view_array['taxonomy_type'] )
	) {
		$view_array['taxonomy_type'] = array( 'category' );
	}
	if (
		! isset( $view_array['roles_type'] )
		|| empty( $view_array['roles_type'] )
	) {
		$view_array['roles_type'] = array( 'administrator' );
	}
	if (
		! isset( $view_array['post_type'] )
		|| empty( $view_array['post_type'] )
	) {
		$view_array['post_type'] = array();
	}
	$filter_type = sanitize_text_field( $_POST['filter_type'] );
	$filters = array();
	$filters = apply_filters( 'wpv_filters_add_filter', $filters, $view_array['post_type'], $view_array );
	$filters = apply_filters( 'wpv_taxonomy_filters_add_filter', $filters, $view_array['taxonomy_type'][0] );
	$filters = apply_filters( 'wpv_users_filters_add_filter', $filters, $view_array['roles_type'][0] );
	if ( isset( $filters[ $filter_type ] ) ) {
		$filter = $filters[ $filter_type ];
		$callback = toolset_getarr( $filter, 'callback', null );
		if( is_callable( $callback ) ) {
			if ( isset( $filter['args'] ) ) {
				$callback( $filter['args'] );
			} else {
				$callback();
			}
		}
	}
	die();
}

add_action( 'wp_ajax_wpv_filters_add_archive_filter_row', 'wpv_filters_add_archive_filter_row_callback' );

function wpv_filters_add_archive_filter_row_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		die( "Security check" );
	}
	$nonce = $_POST["wpnonce"];
	if ( ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filters_add_filter_nonce' ) ) {
		die( "Security check" );
	}
	if (
		! isset( $_POST["id"] )
		|| ! is_numeric( $_POST["id"] )
		|| intval( $_POST['id'] ) < 1
	) {
		die( "Security check" );
	}
	if ( empty( $_POST['filter_type'] ) ) {
		die( "Unexpected filter type" );
	}

	$filter_type = sanitize_text_field( $_POST['filter_type'] );
	$filters = array();
	$filters = apply_filters( 'wpv_filters_add_archive_filter', $filters );
	if ( isset( $filters[ $filter_type ] ) ) {
		$filter = $filters[ $filter_type ];
		$callback = toolset_getarr( $filter, 'callback', null );
		if ( is_callable( $callback ) ) {
			if ( isset( $filter['args'] ) ) {
				$callback( $filter['args'] );
			} else {
				$callback();
			}
		}
	}
	die();
}

function wpv_filter_give_group_to_field( $filters ) {

	$postmeta_label = __( 'Custom fields', 'wpv-views' );
	$post_woocommerce_views_meta_label = __( 'WooCommerce Blocks filter fields', 'wpv-views' );
	$usermeta_label = __( 'User fields', 'wpv-views' );
	$termmeta_label = __( 'Taxonomy fields', 'wpv-views' );

	$groups = array();

	foreach ( $filters as $type => $filter ) {
		if ( isset( $filter['group'] ) ) {
			$group = $filter['group'];
			$groups[$group][$type] = $filter;
		} else if ( strpos( $type, 'custom-field-wpcf-' ) !== false ) {
				$g = '';
				$nice_name = explode('custom-field-wpcf-', $type);
				$id = ( isset($nice_name[1] ) ) ? $nice_name[1] : $type;
				if( function_exists('wpcf_admin_fields_get_groups_by_field') )
				{
					foreach( wpcf_admin_fields_get_groups_by_field( $id, TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, true ) as $gs )
					{
						$g = $gs['name'];
					}
				}
				$gr = $g ? $g : $postmeta_label;

				$groups[$gr][$type] = $filter;
		} else if ( strpos( $type, 'custom-field-views_woo_' ) !== false ) {
			$g = '';
			$nice_name = explode('custom-field-', $type);
	    		$id = ( isset($nice_name[1] ) ) ? $nice_name[1] : $type;
			if( function_exists('wpcf_admin_fields_get_groups_by_field') )
			{
				foreach( wpcf_admin_fields_get_groups_by_field( $id ) as $gs )
				{
					$g = $gs['name'];
				}
			}
			$gr = $g ? $g : $post_woocommerce_views_meta_label;
			$groups[$gr][$type] = $filter;
        } else if ( strpos( $type, 'usermeta-field-wpcf-' ) !== false ) {
                $g = '';
                $nice_name = explode('usermeta-field-wpcf-', $type);
                $id = ( isset($nice_name[1] ) ) ? $nice_name[1] : $type;
                if( function_exists('wpcf_admin_fields_get_groups_by_field') )
                {
                    foreach( wpcf_admin_fields_get_groups_by_field( $id, 'wp-types-user-group' ) as $gs )
                    {
                        $g = $gs['name'];
                    }
                }
                $gr = $g ? $g : $usermeta_label;
                $groups[$gr][$type] = $filter;
		} else if ( strpos( $type, 'termmeta-field-wpcf-' ) !== false ) {
                $g = '';
                $nice_name = explode('termmeta-field-wpcf-', $type);
                $id = ( isset($nice_name[1] ) ) ? $nice_name[1] : $type;
                if( function_exists('wpcf_admin_fields_get_groups_by_field') )
                {
                    foreach( wpcf_admin_fields_get_groups_by_field( $id, 'wp-types-term-group' ) as $gs )
                    {
                        $g = $gs['name'];
                    }
                }
                $gr = $g ? $g : $termmeta_label;
                $groups[$gr][$type] = $filter;
        } else if (
			strpos( $type, 'usermeta-field-' ) !== false
			&& strpos( $type, 'usermeta-field-basic-') === false
			&& strpos( $type, 'usermeta-field-wpcf-' ) === false
		) {
                $gr = $usermeta_label;
                $groups[$gr][$type] = $filter;
		} else if (
			strpos( $type, 'termmeta-field-' ) !== false
			&& strpos( $type, 'termmeta-field-basic-') === false
			&& strpos( $type, 'termmeta-field-wpcf-' ) === false
		) {
                $gr = $termmeta_label;
                $groups[$gr][$type] = $filter;
		} else {
			$groups[$postmeta_label][$type] = $filter;
		}
	}
	return $groups;
}


function wpv_filters_add_filter_select($view_settings) {
	$filters = array();
	if ( ! isset( $view_settings['post_type'] ) ) {
		$view_settings['post_type'] = array();
	}
	if ( ! isset( $view_settings['taxonomy_type'] ) ) {
		$view_settings['taxonomy_type'] = array( 'category' );
	}
	if ( ! isset( $view_settings['roles_type'] ) ) {
		$view_settings['roles_type'] = array( 'users' );
	}
	if (
		isset( $view_settings['query_type'] )
		&& isset( $view_settings['query_type'][0] )
	) {
		switch ( $view_settings['query_type'][0] ) {
			case 'posts':
				$filters = apply_filters( 'wpv_filters_add_filter', $filters, $view_settings['post_type'], $view_settings );
				break;
			case 'taxonomy':
				$filters = apply_filters( 'wpv_taxonomy_filters_add_filter', $filters, $view_settings['taxonomy_type'][0] );
				break;
			case 'users':
				$filters = apply_filters( 'wpv_users_filters_add_filter', $filters, $view_settings['roles_type'][0] );
				break;
		}
	}
	?>
	<select id="filter-add-select" class="js-filter-add-select">
	<option value="-1" disabled="disabled" selected="selected"><?php echo __('--- Please select ---', 'wpv-views'); ?></option>
	<?php
	foreach ( wpv_filter_give_group_to_field( $filters ) as $group => $f ) {
		if (
			$f
			&& ! empty( $f )
		) {
		?>
		<optgroup label="<?php echo esc_attr( $group ); ?>">
		<?php
		foreach ( $f as $type => $filter ) {
			if ( false === WPV_Editor_Query_Filter::is_filter_in_use( $view_settings, $filter['present'] ) ) {
				?>
				<option value="<?php echo esc_attr( $type ); ?>"><?php echo $filter['name']; ?></option>
				<?php
			}
		}
		?>
		</optgroup>
		<?php
		}
	}
	?>
	</select>
	<?php
}

function wpv_filters_add_archive_filter_select( $view_settings ) {
	$filters = array();
	$filters = apply_filters( 'wpv_filters_add_archive_filter', $filters );
	?>
	<select id="filter-add-select" class="js-filter-add-select">
	<option value="-1"><?php echo __('--- Please select ---', 'wpv-views'); ?></option>
	<?php
	foreach ( wpv_filter_give_group_to_field( $filters ) as $group => $f ) {
		if (
			$f
			&& ! empty( $f )
		) {
		?>
		<optgroup label="<?php echo esc_attr( $group ); ?>">
		<?php
		foreach ( $f as $type => $filter ) {
			if ( false === WPV_Editor_Query_Filter::is_filter_in_use( $view_settings, $filter['present'] ) ) {
				?>
				<option value="<?php echo esc_attr( $type ); ?>"><?php echo $filter['name']; ?></option>
				<?php
			}
		}
		?>
		</optgroup>
		<?php
		}
	}
	?>
	</select>
	<?php
}

add_action( 'wp_ajax_wpv_filters_add_query_filter_dialog', 'wpv_filters_add_query_filter_dialog_callback' );

function wpv_filters_add_query_filter_dialog_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	if (
		! isset( $_GET["wpnonce"] )
		|| ! wp_verify_nonce( $_GET["wpnonce"], 'wpv_view_filters_add_filter_nonce' )
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}

	$view_id = (int) sanitize_text_field( toolset_getget( 'id', '' ) );
	/**
	 * Filter the View Settings array to be used for rendering the Query Filter list assigned to a View.
	 *
	 * It is used to inject the automatic query filter generation data for the case of a View listing related content.
	 *
	 * @param array  $view_settings_stored
	 * @param string $view_id
	 */
	$view_settings = apply_filters(
		'wpv_filter_wpv_get_view_settings_for_query_filter_rendering',
		get_post_meta( $view_id, WPV_View_Base::POSTMETA_VIEW_SETTINGS, true ),
		$view_id
	);
	$view_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $view_settings );

	ob_start();
	?>
	<div class="wpv-dialog wpv-dialog-query-filter">
		<label for="filter-add-select" class="label-alignleft"><?php _e('Filter by:', 'wpv-views'); ?></label>
		<?php wpv_filters_add_filter_select( $view_settings ); ?>
	</div>
	<?php
	$dialog = ob_get_clean();
	$data = array(
		'dialog' => $dialog
	);
	wp_send_json_success( $data );
}

add_action( 'wp_ajax_wpv_filters_add_archive_query_filter_dialog', 'wpv_filters_add_archive_query_filter_dialog_callback' );

function wpv_filters_add_archive_query_filter_dialog_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	if (
		! isset( $_GET["wpnonce"] )
		|| ! wp_verify_nonce( $_GET["wpnonce"], 'wpv_view_filters_add_filter_nonce' )
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}
	$view_array = get_post_meta( $_GET["id"], '_wpv_settings', true );
	ob_start();
	?>
	<div class="wpv-dialog wpv-dialog-query-filter">
		<label for="filter-add-select" class="label-alignleft"><?php _e('Filter by:', 'wpv-views'); ?></label>
		<?php wpv_filters_add_archive_filter_select( $view_array ); ?>
	</div>
	<?php
	$dialog = ob_get_clean();
	$data = array(
		'dialog' => $dialog
	);
	wp_send_json_success( $data );
}

// @todo add proper wp_send_json_error/wp_send_json_success management here

add_action( 'wp_ajax_wpv_filter_update_filters_list', 'wpv_filter_update_filters_list_callback' );

function wpv_filter_update_filters_list_callback() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		die( "Security check" );
	}
	if ( ! wp_verify_nonce( $_POST["nonce"], 'wpv_view_filter_update_filters_list_nonce') ) {
		die( "Security check" );
	}
	if (
		! isset( $_POST["id"] )
		|| ! is_numeric( $_POST["id"] )
		|| intval( $_POST['id'] ) < 1
	) {
		die( "Security check" );
	}
	$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
	$return_result = array();
	// Filters list
	$filters_list = '';
	ob_start();
	wpv_display_filters_list( $view_array );
	$filters_list = ob_get_contents();
	ob_end_clean();
	$return_result['wpv_filter_update_filters_list'] = $filters_list;
	$return_result['success'] = $_POST['id'];
	echo json_encode( $return_result );
	die();
}

// @todo add proper wp_send_json_error/wp_send_json_success management here

add_action( 'wp_ajax_wpv_filter_make_intersection_filters', 'wpv_filter_make_intersection_filters' );

function wpv_filter_make_intersection_filters() {
	if ( ! current_user_can( EDIT_VIEWS ) ) {
		die( "Security check" );
	}
	if (! wp_verify_nonce( $_POST["nonce"], 'wpv_view_make_intersection_filters' ) ) {
		die( "Security check" );
	}
	if (
		! isset( $_POST["id"] )
		|| ! is_numeric( $_POST["id"] )
		|| intval( $_POST['id'] ) < 1
	) {
		die( "Security check" );
	}
	$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
	$view_array['taxonomy_relationship'] = 'AND';
	$view_array['custom_fields_relationship'] = 'AND';
	$view_array['usermeta_fields_relationship'] = 'AND';
	update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
	do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
	$return_result = array();
	// Filters list
	$filters_list = '';
	ob_start();
	wpv_display_filters_list( $view_array );
	$filters_list = ob_get_contents();
	ob_end_clean();
	$return_result['wpv_filter_update_filters_list'] = $filters_list;
	$return_result['success'] = $_POST['id'];
	echo json_encode( $return_result );
	die();
}

function wpv_display_filters_list( $view_settings ) {
	$view_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $view_settings );
	if (
		isset( $view_settings['view-query-mode'] )
		&& $view_settings['view-query-mode'] != 'normal'
	) {
		$query_type = 'posts';
	} else if ( isset( $view_settings['query_type'][0] ) ) {
		$query_type = $view_settings['query_type'][0];
	}
	switch ( $query_type ) {
		case 'posts':
			do_action( 'wpv_add_filter_list_item', $view_settings );
			break;
		case 'taxonomy':
			do_action( 'wpv_add_taxonomy_filter_list_item', $view_settings );
			break;
		case 'users':
			do_action( 'wpv_add_users_filter_list_item', $view_settings );
			break;
	}
}

/**
* WPV_Filter_Item
*
* Class to display several common elements in the Query Filters section, mainly for list items
*
* @since 1.7.0
*/

class WPV_Filter_Item {

	public static function simple_filter_list_item( $filter_slug = 'slug', $filter_target = 'posts', $li_slug = 'slug', $title = 'Filter', $content = '' ) {
		?>
		<li id="js-row-<?php echo esc_attr( $filter_slug ); ?>" data-filterslug="<?php echo esc_attr( $filter_slug ); ?>" class="js-filter-row js-filter-row-simple js-wpv-filter-row-<?php echo esc_attr( $li_slug ); ?> js-filter-for-<?php echo esc_attr( $filter_target ); ?> js-filter-<?php echo esc_attr( $li_slug ); ?> js-filter-row-<?php echo esc_attr( $filter_slug ); ?>">
			<span class="wpv-filter-title">
				<i class="icon-filter fa fa-filter"></i>&nbsp;&nbsp;<?php echo $title; ?>
			</span>
			<?php
			echo $content;
			?>
		</li>
		<?php
	}

	public static function simple_filter_list_item_buttons( $li_slug = 'slug', $save_action = '', $save_nonce = '', $delete_action = '', $delete_nonce = '' ) {
		?>
		<span class='edit-filter wpv-edit-filter js-wpv-filter-edit-controls'>
				<button class='button button-secondary button-small js-wpv-filter-remove js-filter-remove' title='<?php echo esc_attr( __('Delete this filter', 'wpv-views') ); ?>' data-deleteaction="<?php echo esc_attr( $delete_action ); ?>" data-nonce='<?php echo esc_attr( $delete_nonce ); ?>'>
				<i class='icon-trash fa fa-trash'></i>
				<?php _e( 'Delete', 'wpv-views' ); ?>
			</button>
			<button class='button button-secondary button-small js-wpv-filter-edit-open js-wpv-filter-<?php echo esc_attr( $li_slug ); ?>-edit-open' title='<?php echo esc_attr( __('Edit this filter','wpv-views') ); ?>'>
				<i class='icon-edit fa fa-pencil-square-o'></i>
				<?php _e( 'Edit', 'wpv-views' ); ?>
			</button>
			<button class="button button-secondary button-small js-wpv-filter-edit-ok js-wpv-filter-<?php echo esc_attr( $li_slug ); ?>-edit-ok hidden" data-save="<?php echo esc_attr( __('Save', 'wpv-views') ); ?>" data-close="<?php echo esc_attr( __('Close', 'wpv-views') ); ?>" data-success="<?php echo esc_attr( __('Updated', 'wpv-views') ); ?>" data-unsaved="<?php echo esc_attr( __('Not saved', 'wpv-views') ); ?>" data-saveaction="<?php echo esc_attr( $save_action ); ?>" data-nonce="<?php echo esc_attr( $save_nonce ); ?>">
				<i class='icon-chevron-up fa fa-chevron-up'></i>
				<?php  _e('Close', 'wpv-views'); ?>
			</button>
		</span>
		<?php
	}

	public static function multiple_filter_list_item( $filter_slug = 'slug', $filter_target = 'posts', $title = 'Filter', $content = '' ) {
		?>
		<li id="js-row-<?php echo esc_attr( $filter_slug ); ?>" data-filterslug="<?php echo esc_attr( $filter_slug ); ?>" class="filter-row-multiple js-filter-row js-filter-row-multiple js-wpv-filter-row-<?php echo esc_attr( $filter_slug ); ?> js-filter-for-<?php echo esc_attr( $filter_target ); ?> js-filter-<?php echo esc_attr( $filter_slug ); ?> js-filter-row-<?php echo esc_attr( $filter_slug ); ?>">

			<span class="wpv-filter-title">
				<i class="icon-filter fa fa-filter"></i>&nbsp;&nbsp;<?php echo $title; ?>
			</span>
			<?php
			echo $content;
			?>
		</li>
		<?php
	}

	public static function filter_list_item_buttons( $li_slug = 'slug', $save_action = '', $save_nonce = '', $delete_action = '', $delete_nonce = '' ) {
		?>
		<span class='edit-filter wpv-edit-filter js-wpv-filter-edit-controls'>
			<button class='button button-secondary button-small js-wpv-filter-remove js-filter-remove js-wpv-filter-remove-<?php echo esc_attr( $li_slug ); ?>' title='<?php echo esc_attr( __('Delete this filter', 'wpv-views') ); ?>' data-deleteaction="<?php echo esc_attr( $delete_action ); ?>" data-nonce='<?php echo esc_attr( $delete_nonce ); ?>'>
				<i class='icon-trash fa fa-trash'></i>
				<?php _e( 'Delete', 'wpv-views' ); ?>
			</button>
			<button class='button button-secondary button-small js-wpv-filter-edit-open js-wpv-filter-<?php echo esc_attr( $li_slug ); ?>-edit-open' title='<?php echo esc_attr( __('Edit this filter','wpv-views') ); ?>'>
				<i class='icon-edit fa fa-pencil-square-o'></i>
				<?php _e( 'Edit', 'wpv-views' ); ?>
			</button>
			<button class="button button-secondary button-small js-wpv-filter-edit-ok js-wpv-filter-<?php echo esc_attr( $li_slug ); ?>-edit-ok hidden" data-save="<?php echo esc_attr( __('Save', 'wpv-views') ); ?>" data-close="<?php echo esc_attr( __('Close', 'wpv-views') ); ?>" data-success="<?php echo esc_attr( __('Updated', 'wpv-views') ); ?>" data-unsaved="<?php echo esc_attr( __('Not saved', 'wpv-views') ); ?>" data-saveaction="<?php echo esc_attr( $save_action ); ?>" data-nonce="<?php echo esc_attr( $save_nonce ); ?>">
				<i class='icon-chevron-up fa fa-chevron-up'></i>
				<?php  _e('Close', 'wpv-views'); ?>
			</button>
		</span>
		<?php
	}

	public static function get_custom_filter_function_and_value( $value ) {
		$trim = trim( $value );
		$function = 'constant';
		$return_val = $value;
		$text_boxes = 1;
		$singles = array(
			'url' => '/^URL_PARAM\((.*?)\)/',
			'attribute' => '/^VIEW_PARAM\((.*?)\)/',
			'framework' => '/^FRAME_KEY\((.*?)\)/',
			'future_day' => '/^FUTURE_DAY\((.*?)\)/',
			'past_day' => '/^PAST_DAY\((.*?)\)/',
			'future_month' => '/^FUTURE_MONTH\((.*?)\)/',
			'past_month' => '/^PAST_MONTH\((.*?)\)/',
			'future_year' => '/^FUTURE_YEAR\((.*?)\)/',
			'past_year' => '/^PAST_YEAR\((.*?)\)/',
			'future_one' => '/^FUTURE_ONE\((.*?)\)/',
			'past_one' => '/^PAST_ONE\((.*?)\)/',
			'seconds_from_now' => '/^SECONDS_FROM_NOW\((.*?)\)/',
			'months_from_now' => '/^MONTHS_FROM_NOW\((.*?)\)/',
			'years_from_now' => '/^YEARS_FROM_NOW\((.*?)\)/',
			'date' => '/^DATE\((.*?)\)/'
		);
		foreach ( $singles as $code => $pattern ) {
			if ( preg_match( $pattern, $trim, $matches ) == 1 ) {
				$function = $code;
				$return_val = $matches[1];
				break;
			}
		}
		$zeros = array(
			'now' => '/^NOW\((.*?)\)/',
			'today' => '/^TODAY\((.*?)\)/',
			'this_month' => '/^THIS_MONTH\((.*?)\)/',
			'this_year' => '/^THIS_YEAR\((.*?)\)/',
			'current_one' => '/^CURRENT_ONE\((.*?)\)/'
		);
		foreach ( $zeros as $code => $pattern ) {
			if ( preg_match( $pattern, $trim, $matches ) == 1 ) {
				$function = $code;
				$return_val = '';
				$text_boxes = 0;
				break;
			}
		}
		$return_val = str_replace( '####coma####', ',', $return_val );
		return array( 'function' => $function, 'value' => $return_val, 'text_boxes' => $text_boxes );
	}

	//function _wpv_encode_date( $value ) {
	public static function encode_date( $value ) {
		if ( preg_match_all( '/DATE\(([\\d,-]*)\)/', $value, $matches ) ) {
			foreach( $matches[0] as $match ) {
				$value = str_replace( $match, str_replace( ',', '####coma####', $match ), $value );
			}
		}
		return $value;
	}

	//function _wpv_unencode_date( $value ) {
	public static function unencode_date( $value ) {
		return str_replace( '####coma####', ',', $value );
	}

	public static function date_field_controls( $function, $value ) {
		global $wp_locale;
		if ( $function == 'date' ) {
			$date_parts = explode( ',', $value );
			$time_adj = mktime( 0, 0, 0, $date_parts[1], $date_parts[0], $date_parts[2] );
		} else {
			$time_adj = current_time( 'timestamp' );
		}
		$jj = gmdate( 'd', $time_adj );
		$mm = gmdate( 'm', $time_adj );
		$aa = gmdate( 'Y', $time_adj );
		?>
		<span class="js-wpv-custom-field-date js-wpv-termmeta-field-date js-wpv-usermeta-field-date">
			<select autocomplete="off">
			<?php
			for ( $i = 1; $i < 13; $i = $i +1 ) {
				$monthnum = zeroise( $i, 2 );
				?>
				<option value="<?php echo esc_attr( $monthnum ); ?>" <?php selected( $monthnum, $mm ); ?>><?php echo $monthnum . ' - ';echo $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ); ?></option>
				<?php
			}
			?>
			</select>
			<input class="js-wpv-filter-maybe-validate" data-type="day" type="text" value="<?php echo esc_attr( $jj ); ?>" size="2" maxlength="2" autocomplete="off" />
			<input class="js-wpv-filter-maybe-validate" data-type="year" type="text" value="<?php echo esc_attr( $aa ); ?>" size="4" maxlength="4" autocomplete="off" />
		</span>
		<?php
	}

}
