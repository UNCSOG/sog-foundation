<?php

/**
* Status filter
*
* @package Views
*
* @since unknown
*/

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Status_Filter::on_load();

/**
* WPV_Search_Filter
*
* Views Status Filter Class
*
* @since 1.7
* @since 2.1	Added to WordPress Archives
* @since 2.1	Include this file only when editing a View or WordPress Archive, or when doing AJAX
*/

class WPV_Status_Filter {

    static function on_load() {
        add_action( 'init',			 array( 'WPV_Status_Filter', 'init' ) );
		add_action( 'admin_init',	 array( 'WPV_Status_Filter', 'admin_init' ) );
		add_action( 'rest_api_init', array( 'WPV_Status_Filter', 'admin_init' ) );
    }

    static function init() {
		wp_register_script(
			'views-filter-status-js',
			WPV_URL . "/res/js/filters/views_filter_status.js",
			array( 'views-filters-js'),
			WPV_VERSION,
			false
		);
    }

	static function admin_init() {
		// Register filters in dialogs
		add_filter( 'wpv_filters_add_filter',					array( 'WPV_Status_Filter', 'wpv_filters_add_filter_post_status' ), 1, 1 );
		add_filter( 'wpv_filters_add_archive_filter',			array( 'WPV_Status_Filter', 'wpv_filters_add_archive_filter_post_status' ), 1, 1 );
		// Register filters in lists
		add_action( 'wpv_add_filter_list_item',					array( 'WPV_Status_Filter', 'wpv_add_filter_post_status_list_item' ), 1, 1 );
		// Update and delete
		add_action( 'wp_ajax_wpv_filter_post_status_update',	array( 'WPV_Status_Filter', 'wpv_filter_post_status_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_post_status_delete',	array( 'WPV_Status_Filter', 'wpv_filter_post_status_delete_callback' ) );
		// Scripts
		add_action( 'admin_enqueue_scripts',					array( 'WPV_Status_Filter','admin_enqueue_scripts' ), 20 );
		// TODO This might not be needed here, maybe for summary filter
		//add_action( 'wp_ajax_wpv_filter_status_sumary_update', array( 'WPV_Status_Filter', 'wpv_filter_post_status_sumary_update_callback' ) );
    }

	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 1.7
	*/

	static function admin_enqueue_scripts( $hook ) {
		wp_enqueue_script( 'views-filter-status-js' );
	}

	/**
	* wpv_filters_add_filter_post_status
	*
	* Register the status filter in the popup dialog
	*
	* @param $filters
	*
	* @since unknown
	*/

	static function wpv_filters_add_filter_post_status( $filters ) {
		$filters['post_status'] = array(
			'name'		=> __( 'Post status', 'wpv-views' ),
			'present'	=> 'post_status',
			'callback'	=> array( 'WPV_Status_Filter', 'wpv_add_new_filter_status_list_item' ),
			'group'		=> __( 'Post filters', 'wpv-views' )
		);
		return $filters;
	}

	/**
	* wpv_filters_add_archive_filter_post_status
	*
	* Register the status filter in the popup dialog on WPAs.
	*
	* @param $filters
	*
	* @since 2.1
	*/

	static function wpv_filters_add_archive_filter_post_status( $filters ) {
		$filters['post_status'] = array(
			'name'		=> __( 'Post status', 'wpv-views' ),
			'present'	=> 'post_status',
			'callback'	=> array( 'WPV_Status_Filter', 'wpv_add_new_archive_filter_status_list_item' ),
			'group'		=> __( 'Post filters', 'wpv-views' )
		);
		return $filters;
	}

	/**
	* wpv_add_new_filter_status_list_item
	*
	* Register the status filter in the filters list
	*
	* @since unknown
	*/

	static function wpv_add_new_filter_status_list_item() {
		$args = array(
			'view-query-mode'	=> 'normal',
			'post_status'		=> array()
		);
		WPV_Status_Filter::wpv_add_filter_post_status_list_item( $args );
	}

	/**
	* wpv_add_new_archive_filter_status_list_item
	*
	* Register the status filter in the filters list on WPAs.
	*
	* @since 2.1
	*/

	static function wpv_add_new_archive_filter_status_list_item() {
		$args = array(
			'view-query-mode'	=> 'archive',
			'post_status'		=> array()
		);
		WPV_Status_Filter::wpv_add_filter_post_status_list_item( $args );
	}

	/**
	* wpv_add_filter_post_status_list_item
	*
	* Render status filter item in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_add_filter_post_status_list_item( $view_settings ) {
		if ( isset( $view_settings['post_status'] ) ) {
			$li = WPV_Status_Filter::wpv_get_list_item_ui_post_status( $view_settings );
			WPV_Filter_Item::simple_filter_list_item( 'post_status', 'posts', 'post-status', __( 'Post status filter', 'wpv-views' ), $li );
		}
	}

	/**
	* wpv_get_list_item_ui_post_status
	*
	* Render status filter item content in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_get_list_item_ui_post_status( $view_settings = array() ) {
		if ( ! isset( $view_settings['post_status'] ) || ! is_array( $view_settings['post_status'] ) ) {
			$view_settings['post_status'] = array();
		}
		ob_start();
		?>
		<p class='wpv-filter-post-status-summary js-wpv-filter-summary js-wpv-filter-post-status-summary'>
			<?php echo wpv_get_filter_status_summary_txt( $view_settings ); ?>
		</p>
		<?php
		WPV_Filter_Item::simple_filter_list_item_buttons( 'post-status', 'wpv_filter_post_status_update', wp_create_nonce( 'wpv_view_filter_post_status_nonce' ), 'wpv_filter_post_status_delete', wp_create_nonce( 'wpv_view_filter_post_status_delete_nonce' ) );
		?>
		<div id="wpv-filter-post-status-edit" class="wpv-filter-edit js-wpv-filter-edit">
			<div id="wpv-filter-post-status" class="js-wpv-filter-options js-wpv-filter-post-status-options js-filter-post-status-list">
				<?php WPV_Status_Filter::wpv_render_post_status_options( $view_settings ); ?>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
		</div>
		<?php
		$res = ob_get_clean();
		return $res;
	}

	/**
	* wpv_filter_post_status_update_callback
	*
	* Update status filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_post_status_update_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_post_status_nonce' )
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
		$view_id = intval( $_POST['id'] );
		$view_array = get_post_meta( $view_id, '_wpv_settings', true );
		$changed = false;
		if (
			empty( $_POST['filter_options'] )
			&& isset( $view_array['post_status'] )
		) {
			unset ( $view_array['post_status'] );
			$changed = true;
		} else {
			parse_str( $_POST['filter_options'], $filter_status );
			if (
				! isset( $view_array['post_status'] )
				|| $view_array['post_status'] != $filter_status['post_status']
			) {
				if ( is_array( $filter_status['post_status'] ) ) {
					$filter_status['post_status'] = array_map( 'sanitize_text_field', $filter_status['post_status'] );
				} else {
					$filter_status['post_status'] = sanitize_text_field( $filter_status['post_status'] );
				}
				$changed = true;
				$view_array['post_status'] = $filter_status['post_status'];
			}
		}
		if ( $changed ) {
			update_post_meta( $view_id, '_wpv_settings', $view_array );
			do_action( 'wpv_action_wpv_save_item', $view_id );
		}
		if ( ! isset( $filter_status['post_status'] ) ) {
			$filter_status['post_status'] = array();
		}
		$data = array(
			'id' => $view_id,
			'message' => __( 'Post status filter saved', 'wpv-views' ),
			'summary' => wpv_get_filter_status_summary_txt( $filter_status )
		);
		wp_send_json_success( $data );
	}

	/**
	* Update status filter summary callback
	*/
	/*
	static function wpv_filter_post_status_sumary_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_post_status_nonce' ) ) {
			die( "Security check" );
		}
		parse_str( $_POST['filter_status'], $filter_status );
		if ( ! isset($filter_status['post_status'] ) ) {
			$filter_status['post_status'] = array();
		}
		echo wpv_get_filter_status_summary_txt( $filter_status );
		die();
	}
	*/

	/**
	* wpv_filter_post_status_delete_callback
	*
	* Delete status filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_post_status_delete_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_post_status_delete_nonce' )
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
		if ( isset( $view_array['post_status'] ) ) {
			unset( $view_array['post_status'] );
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
		$data = array(
			'id' => $_POST["id"],
			'message' => __( 'Post status filter deleted', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

	/**
	* wpv_render_status_options
	*
	* Render status filter options
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_render_post_status_options( $view_settings = array() ) {
        // WordPress default statuses
		$wp_statuses = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' );

		// All the statuses at this WordPress instance
		$all_statuses = get_post_stati();

		// Maintain the order of the default statuses and add custom ones after them
		$custom_statuses = array_diff( $all_statuses, $wp_statuses );
		$statuses = array_merge( $wp_statuses, $custom_statuses );

		// Finally, include "any" as the last option
		$statuses[] = 'any';

		$selected = ( isset( $view_settings['post_status'] ) &&  is_array( $view_settings['post_status'] ) ) ? $view_settings['post_status'] : array() ;
		?>
		<h4><?php  _e( 'Check statuses', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set wpv-mightlong-list">
		<?php
		foreach( $statuses as $status ) {
			if ( in_array( $status, $selected ) ) {
				$checked = ' checked="checked"';
			} else {
				$checked = '';
			}
			?>
			<li>
				<input type="checkbox" id="wpv-filter-status-<?php echo esc_attr( $status ); ?>" name="post_status[]" value="<?php echo esc_attr( $status ); ?>"<?php echo $checked; ?> />
				<label for="wpv-filter-status-<?php echo esc_attr( $status ); ?>"><?php echo $status; ?></label>
			</li>
			<?php
		}
		?>
		</ul>
		<?php
	}

}
