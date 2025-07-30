<?php
/**
* Sticky Filter
*
* @package Views
*
* @since 1.10
*/

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Sticky_Filter::on_load();

/**
* WPV_Sticky_Filter
*
* Views Sticky Filter Class
*
* @since 1.10
* @since 2.1	Added to WordPress Archives
* @since 2.1	Include this file only when editing a View or WordPress Archive, or when doing AJAX
*/
class WPV_Sticky_Filter {

    /**
	* Register scripts before initializing this class
	*/

    static function on_load() {
        add_action( 'init',			 array( 'WPV_Sticky_Filter', 'init' ) );
        add_action( 'admin_init',	 array( 'WPV_Sticky_Filter', 'admin_init' ) );
        add_action( 'rest_api_init', array( 'WPV_Sticky_Filter', 'admin_init' ) );
    }

    /**
	* Add frontend hooks
	*/

    static function init() {
        wp_register_script(
			'views-filter-sticky-js',
			WPV_URL . '/res/js/filters/views_filter_sticky.js',
			array( 'views-filters-js', 'underscore' ),
			WPV_VERSION,
			false
		);
		$sticky_strings = array(
			'post'		=> array(
								'post_type_not_supported'		=> __( 'Only posts from the Post post type can be sticky', 'wpv-views' ),
							),
			'archive'	=> array(
								'disable_post_sticky_filter'	=> __( 'Only posts from the Post post type can be sticky', 'wpv-views' ),
							),
		);
		wp_localize_script( 'views-filter-sticky-js', 'wpv_sticky_strings', $sticky_strings );
    }

    /**
	* Add backend hooks
	*/

    static function admin_init() {
        // Register filters in dialogs
        add_filter( 'wpv_filters_add_filter',					array( 'WPV_Sticky_Filter', 'wpv_filters_add_filter_post_sticky' ), 1, 1 );
		add_filter( 'wpv_filters_add_archive_filter',			array( 'WPV_Sticky_Filter', 'wpv_filters_add_archive_filter_post_sticky' ), 1, 1 );
		// Register filters in lists
        add_action( 'wpv_add_filter_list_item',					array( 'WPV_Sticky_Filter', 'wpv_add_filter_post_sticky_list_item' ), 1, 1 );
		// Update and delete
        add_action( 'wp_ajax_wpv_filter_post_sticky_update',	array( 'WPV_Sticky_Filter', 'wpv_filter_post_sticky_update_callback' ) );
        add_action( 'wp_ajax_wpv_filter_post_sticky_delete',	array( 'WPV_Sticky_Filter', 'wpv_filter_post_sticky_delete_callback' ) );
        // Scripts
        add_action( 'admin_enqueue_scripts',					array( 'WPV_Sticky_Filter', 'admin_enqueue_scripts' ), 20 );
		//add_action( 'wp_ajax_wpv_filter_sticky_sumary_update', array( 'WPV_Sticky_Filter', 'wpv_filter_post_sticky_sumary_update_callback' ) );
    }

    /**
	* admin_enqueue_scripts
	*
	* Register required scripts for this filter
	*/

    static function admin_enqueue_scripts( $hook ) {
        wp_enqueue_script( 'views-filter-sticky-js' );
    }

    /**
	* wpv_filters_add_filter_post_sticky
	*
	* Register filter in the popup dialog
	*
	* @param $filters
	*/

    static function wpv_filters_add_filter_post_sticky( $filters ) {

        $filters['post_sticky'] = array(
            'name'		=> __( 'Post stickiness', 'wpv-views' ),
            'present'	=> 'post_sticky',
            'callback'	=> array( 'WPV_Sticky_Filter', 'wpv_add_new_filter_sticky_list_item' ),
            'group'		=> __( 'Post filters', 'wpv-views' )
        );
        return $filters;
    }

	/**
	* wpv_filters_add_archive_filter_post_sticky
	*
	* Register filter in the popup dialog on WPAs.
	*
	* @param $filters
	*
	* @since 2.1
	*/

    static function wpv_filters_add_archive_filter_post_sticky( $filters ) {

        $filters['post_sticky'] = array(
            'name'		=> __( 'Post stickiness', 'wpv-views' ),
            'present'	=> 'post_sticky',
            'callback'	=> array( 'WPV_Sticky_Filter', 'wpv_add_new_archive_filter_sticky_list_item' ),
            'group'		=> __( 'Post filters', 'wpv-views' )
        );
        return $filters;
    }

    /**
	* wpv_add_new_filter_sticky_list_item
	*
	* Register the sticky filter in the filters list
	*/

    static function wpv_add_new_filter_sticky_list_item() {
        $args = array(
			'view-query-mode'	=> 'normal',
            'post_sticky'		=> 'include'
        );
        WPV_Sticky_Filter::wpv_add_filter_post_sticky_list_item( $args );
    }

	/**
	* wpv_add_new_archive_filter_sticky_list_item
	*
	* Register the sticky filter in the filters list on WPAs.
	*
	* @since 2.1
	*/

    static function wpv_add_new_archive_filter_sticky_list_item() {
        $args = array(
			'view-query-mode'	=> 'archive',
            'post_sticky'		=> 'include'
        );
        WPV_Sticky_Filter::wpv_add_filter_post_sticky_list_item( $args );
    }

    /**
     * wpv_add_filter_post_sticky_list_item
     *
     * Render sticky filter item in the filters list
     *
     * @param $view_settings
     */
    static function wpv_add_filter_post_sticky_list_item( $view_settings ) {
        if ( isset( $view_settings['post_sticky'] ) ) {
            $li = WPV_Sticky_Filter::wpv_get_list_item_ui_post_sticky( $view_settings );
            WPV_Filter_Item::simple_filter_list_item( 'post_sticky', 'posts', 'post_sticky', __( 'Post stickiness filter', 'wpv-views' ), $li );
        }
    }

    /**
     * wpv_get_list_item_ui_post_sticky
     *
     * Render sticky filter item content in the filters list
     *
     * @param $view_settings
     */
    static function wpv_get_list_item_ui_post_sticky( $view_settings = array() ) {
        if ( ! isset( $view_settings['post_sticky'] ) ) {
            $view_settings['post_sticky'] = 'include';
        }
        ob_start();
        ?>
        <p class='wpv-filter-post-sticky-summary js-wpv-filter-summary js-wpv-filter-post-sticky-summary'>
        <?php echo wpv_get_filter_sticky_summary_txt( $view_settings ); ?>
        </p>
        <?php
        WPV_Filter_Item::simple_filter_list_item_buttons( 'post_sticky', 'wpv_filter_post_sticky_update', wp_create_nonce( 'wpv_view_filter_post_sticky_nonce' ), 'wpv_filter_post_sticky_delete', wp_create_nonce( 'wpv_view_filter_post_sticky_delete_nonce' ) );
        ?>
        <div id="wpv-filter-post-sticky-edit" class="wpv-filter-edit js-wpv-filter-edit">
            <div id="wpv-filter-post-sticky" class="js-wpv-filter-options js-wpv-filter-post-sticky-options js-filter-post-sticky-list">
				<?php WPV_Sticky_Filter::wpv_render_post_sticky_options( $view_settings ); ?>
            </div>
            <div class="js-wpv-filter-toolset-messages"></div>
        </div>
        <?php
        $res = ob_get_clean();
        return $res;
    }

    /**
     * wpv_filter_post_sticky_update_callback
     *
     * Update sticky posts filter callback
     */
    static function wpv_filter_post_sticky_update_callback() {
        if ( ! current_user_can( EDIT_VIEWS ) ) {
            $data = array(
                'type' => 'capability',
                'message' => __( 'You do not have permissions for that.', 'wpv-views' )
            );
            wp_send_json_error( $data );
        }
        if (
                ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'wpv_view_filter_post_sticky_nonce' )
        ) {
            $data = array(
                'type' => 'nonce',
                'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
            );
            wp_send_json_error( $data );
        }
        if (
                ! isset( $_POST['id'] ) || ! is_numeric( $_POST['id'] ) || intval( $_POST['id'] ) < 1
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
             empty( $_POST['filter_options'] ) && isset( $view_array['post_sticky'] )
        ) {
            unset( $view_array['post_sticky'] );
            $changed = true;
        } else {
            parse_str( $_POST['filter_options'], $filter_sticky );
            if (
				! isset( $view_array['post_sticky'] )
				|| $view_array['post_sticky'] != $filter_sticky['post_sticky']
            ) {
                $filter_sticky['post_sticky'] = sanitize_text_field( $filter_sticky['post_sticky'] );
                $changed = true;
                $view_array['post_sticky'] = $filter_sticky['post_sticky'];
            }
        }
        if ( $changed ) {
            update_post_meta( $view_id, '_wpv_settings', $view_array );
            do_action( 'wpv_action_wpv_save_item', $view_id );
        }
        if ( ! isset( $filter_sticky['post_sticky'] ) ) {
            $filter_sticky['post_sticky'] = true;
        }
        $data = array(
            'id' => $view_id,
            'message' => __( 'Sticky Posts Filter saved', 'wpv-views' ),
            'summary' => wpv_get_filter_sticky_summary_txt( $filter_sticky )
        );
        wp_send_json_success( $data );
    }

	/**
	* Update sticky posts filter summary callback
	*/
	/*
    static function wpv_filter_post_sticky_sumary_update_callback() {
        $nonce = $_POST['wpnonce'];
        if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_post_sticky_nonce' ) ) {
            die( 'Security check' );
        }
        parse_str( $_POST['filter_sticky'], $filter_sticky );
        if ( ! isset( $filter_sticky['post_sticky'] ) ) {
            $filter_sticky['post_sticky'] = true;
        }
        echo wpv_get_filter_sticky_summary_txt( $filter_sticky );
        die();
    }
	*/

    /**
     * wpv_filter_post_sticky_delete_callback
     *
     * Delete sticky posts filter callback
     *
     * @since unknown
     */
    static function wpv_filter_post_sticky_delete_callback() {
        if ( !current_user_can( EDIT_VIEWS ) ) {
            $data = array(
                'type' => 'capability',
                'message' => __( 'You do not have permissions for that.', 'wpv-views' )
            );
            wp_send_json_error( $data );
        }
        if (
                !isset( $_POST['wpnonce'] ) || !wp_verify_nonce( $_POST['wpnonce'], 'wpv_view_filter_post_sticky_delete_nonce' )
        ) {
            $data = array(
                'type' => 'nonce',
                'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
            );
            wp_send_json_error( $data );
        }
        if (
                !isset( $_POST['id'] ) || !is_numeric( $_POST['id'] ) || intval( $_POST['id'] ) < 1
        ) {
            $data = array(
                'type' => 'id',
                'message' => __( 'Wrong or missing ID.', 'wpv-views' )
            );
            wp_send_json_error( $data );
        }

        $view_array = get_post_meta( $_POST['id'], '_wpv_settings', true );
        if ( isset( $view_array['post_sticky'] ) ) {
            unset( $view_array['post_sticky'] );
        }
        update_post_meta( $_POST['id'], '_wpv_settings', $view_array );
        do_action( 'wpv_action_wpv_save_item', $_POST['id'] );
        $data = array(
            'id' => $_POST['id'],
            'message' => __( 'Sticky Posts Filter deleted', 'wpv-views' )
        );
        wp_send_json_success( $data );
    }

    /**
     * wpv_render_sticky_options
     *
     * Render sticky posts filter options
     *
     * @param $view_settings
     */
    static function wpv_render_post_sticky_options( $view_settings = array() ) {
		$defaults = array(
			'view-query-mode'	=> 'normal',
			'post_sticky'		=> 'include'
		);
		$view_settings = wp_parse_args( $view_settings, $defaults );
        ?>
        <h4><?php _e( 'Include or exclude sticky posts', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set">
			<li>
				<input type="radio" name="post_sticky" class="js-wpv-post-sticky-filter" value="include" id="wpv-post-sticky-include" <?php checked( $view_settings['post_sticky'], 'include' ); ?> autocomplete="off" />
				<label for="wpv-post-sticky-include"><?php _e( 'Return only sticky posts', 'wpv-views' ); ?></label>
			</li>
			<li>
				<input type="radio" name="post_sticky" class="js-wpv-post-sticky-filter" value="exclude" id="wpv-post-sticky-exclude" <?php checked( $view_settings['post_sticky'], 'exclude' ); ?> autocomplete="off" />
				<label for="wpv-post-sticky-exclude"><?php _e( 'Return only posts that are not sticky', 'wpv-views' ); ?></label>
			</li>
		</ul>
        <?php
    }

}
