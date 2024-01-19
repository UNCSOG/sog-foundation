<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Handle actions with relationship filters in the view edit page.
 *
 * @since m2m
 */
class WPV_Ajax_Handler_Update_Content_Selection extends Toolset_Ajax_Handler_Abstract {

	/**
	 * WP Nonce.
	 *
	 * @var string
	 * @since m2m
	 */
	const NONCE = 'wpv_view_query_type_nonce';


	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin( array(
			'nonce' => $ajax_manager->get_action_js_name( WPV_Ajax::LEGACY_VIEW_QUERY_TYPE_NONCE ),
			'capability_needed' => EDIT_VIEWS,
		) );

		$view_id = toolset_getpost( 'id' );

		if (
			! is_numeric( $view_id )
			|| intval( $view_id ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
		}

		$query_type = toolset_getpost( 'query_type' );

		$domains = array( 'post', 'taxonomy', 'roles' );
		$content_type_slugs = array(
			'post' => array(),
			'taxonomy' => array(),
			'roles' => array()
		);
		foreach ( $domains as $domain ) {
			$content_type_slugs[ $domain ] = toolset_getpost( $domain . '_type_slugs', array( 'any' ) );
			if ( is_array( $content_type_slugs[ $domain ] ) ) {
				$content_type_slugs[ $domain ] = array_map( 'sanitize_text_field', $content_type_slugs[ $domain ] );
			} else {
				$content_type_slugs[ $domain ] = sanitize_text_field( $content_type_slugs[ $domain ] );
			}
		}

		$changed = false;
		$query_type_changed = false;

		$view_settings = get_post_meta( $view_id, '_wpv_settings', true );

		if (
			! isset( $view_settings['query_type'] )
			|| ! isset( $view_settings['query_type'][0] )
			|| $view_settings['query_type'][0] != $query_type
		) {
			$view_settings['query_type'] = array( sanitize_text_field( $query_type ) );
			$changed = true;
			$query_type_changed = true;
		}

		foreach ( $domains as $domain ) {
			if (
				! isset( $view_settings[ $domain . '_type' ] )
				|| $view_settings[ $domain . '_type' ] != $content_type_slugs[ $domain ]
			) {
				$view_settings[ $domain . '_type' ] = $content_type_slugs[ $domain ];
				$changed = true;
			}
		}

		if ( $changed ) {
			update_post_meta( $view_id, '_wpv_settings', $view_settings );
			do_action( 'wpv_action_wpv_save_item', $view_id );
		}

		// Filters list
		if ( $query_type_changed ) {
			$filters_list = '';
			ob_start();
			wpv_display_filters_list( $view_settings );
			$filters_list = ob_get_contents();
			ob_end_clean();
		} else {
			$filters_list = 'no_change';
		}

		$data = array(
			'id' => $view_id,
			'updated_filters_list' => $filters_list,
			'message' => __( 'Content Selection saved', 'wpv-views' )
		);

		$ajax_manager->ajax_finish(
			$data,
			true
		);
	}

}
