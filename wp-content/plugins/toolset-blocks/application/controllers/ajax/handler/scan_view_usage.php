<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * View Scan usage callback action.
 *
 * Prints JSON-encoded array of items on success. Each item has a 'post_title' and 'link'.
 * Otherwise prints an error message (not a valid JSON).
 *
 * @since unknown
 * @since 2.6.4   Moved here from wpv-admin-ajax.php
 */
class WPV_Ajax_Handler_Scan_View_Usage extends Toolset_Ajax_Handler_Abstract {
	public function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_SCAN_VIEW_USAGE,
				'public' => false,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		$post_id = wpv_getpost( 'id', 0 );
		if ( 0 == $post_id 	) {
			$data = array(
				'message' => __( 'Wrong data', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		global $wpdb, $sitepress;

		$values_to_prepare = array();
		$trans_join = '';
		if( Toolset_WPML_Compatibility::get_instance()->is_wpml_active_and_configured() ) {
			$trans_join = " LEFT JOIN {$wpdb->prefix}icl_translations icl_t on icl_t.element_id = ID AND icl_t.element_type LIKE  \"post_%\" ";
		}

		$view = get_post( $post_id );
		$needle = '[wpv-view name="' . $view->post_title . '"';
		$needle = '%' . wpv_esc_like( $needle ) . '%';
		if (
			'trash' == $view->post_status
			&& '__trashed' === substr( $view->post_name, -9 )
		) {
			$view_name = substr( $view->post_name, 0, -9 );
		} else {
			$view_name = $view->post_name;
		}
		$needle_name = '[wpv-view name="' . $view_name . '"';
		$needle_name = '%' . wpv_esc_like( $needle_name ) . '%';

		$needle_id = '[wpv-view id="' . $view->ID . '"';
		$needle_id = '%' . wpv_esc_like( $needle_id ) . '%';

		$needle_single_quotes = '[wpv-view name=\'' . $view->post_title . '\'';
		$needle_single_quotes = '%' . wpv_esc_like( $needle_single_quotes ) . '%';

		$needle_name_single_quotes = '[wpv-view name=\'' . $view_name . '\'';
		$needle_name_single_quotes = '%' . wpv_esc_like( $needle_name_single_quotes ) . '%';

		$needle_id_single_quotes = '[wpv-view id=\'' . $view->ID . '\'';
		$needle_id_single_quotes = '%' . wpv_esc_like( $needle_id_single_quotes ) . '%';

		// This is to search for "Form only" shortcodes
		$needle_name_form = '[wpv-form-view name="' . $view_name . '"';
		$needle_name_form = '%' . wpv_esc_like( $needle_name_form ) . '%';

		$needle_id_form = '[wpv-form-view id="' . $view->ID . '"';
		$needle_id_form = '%' . wpv_esc_like( $needle_id_form ) . '%';

		$needle_single_quotes_form = '[wpv-form-view name=\'' . $view->post_title . '\'';
		$needle_single_quotes_form = '%' . wpv_esc_like( $needle_single_quotes_form ) . '%';

		$needle_name_single_quotes_form = '[wpv-form-view name=\'' . $view_name . '\'';
		$needle_name_single_quotes_form = '%' . wpv_esc_like( $needle_name_single_quotes_form ) . '%';

		$needle_id_single_quotes_form = '[wpv-form-view id=\'' . $view->ID . '\'';
		$needle_id_single_quotes_form = '%' . wpv_esc_like( $needle_id_single_quotes_form ) . '%';

		$needle_elementor_widget_type = '"widgetType":"toolset-view"';
		$needle_elementor_widget_type = '%' . wpv_esc_like( $needle_elementor_widget_type ) . '%';

		$needle_elementor_widget_settings = '"view":"' . $view->ID . '"';
		$needle_elementor_widget_settings = '%' . wpv_esc_like( $needle_elementor_widget_settings ) . '%';

		$values_to_prepare[] = $needle;
		$values_to_prepare[] = $needle_name;
		$values_to_prepare[] = $needle_single_quotes;
		$values_to_prepare[] = $needle_name_single_quotes;
		$values_to_prepare[] = str_replace( '[', '{!{', $needle );
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_name );
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_single_quotes );
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_name_single_quotes );
		$values_to_prepare[] = $needle_id;
		$values_to_prepare[] = $needle_id_single_quotes;
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_id );
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_id_single_quotes );
		$values_to_prepare[] = $needle_name_form;
		$values_to_prepare[] = $needle_single_quotes_form;
		$values_to_prepare[] = $needle_name_single_quotes_form;
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_name_form );
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_single_quotes_form );
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_name_single_quotes_form );
		$values_to_prepare[] = $needle_id_form;
		$values_to_prepare[] = $needle_id_single_quotes_form;
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_id_form );
		$values_to_prepare[] = str_replace( '[', '{!{', $needle_id_single_quotes_form );
		$values_to_prepare[] = array(
			'relation' => 'AND',
			'terms' => array(
				$needle_elementor_widget_type,
				$needle_elementor_widget_settings,
			),
		);
		$values_to_prepare[] = '%wpv-gutenberg-view-wrapper-' . $view->ID . '%';

		$post_content_where = '';
		$postmeta_where = '';
		$last_value_to_prepare = end( $values_to_prepare );
		$flattened_values_to_prepare = array();
		foreach ( $values_to_prepare as $value ) {
			if ( is_array( $value ) ) {
				$post_content_where .= '( ';
				$postmeta_where .= '( ';
				$last_term = end( $value['terms'] );
				foreach ( $value['terms'] as $term ) {
					$flattened_values_to_prepare[] = $term;
					$post_content_where .= 'post_content LIKE %s ';
					$postmeta_where .= 'meta_value LIKE %s ';
					if ( $term !== $last_term ) {
						// another term is coming...
						$post_content_where .= $value['relation'] . ' ';
						$postmeta_where .= $value['relation'] . ' ';
					}
				}
				$post_content_where .= ' )';
				$postmeta_where .= ' )';
			} else {
				$flattened_values_to_prepare[] = $value;
				$post_content_where .= 'post_content LIKE %s ';
				$postmeta_where .= 'meta_value LIKE %s ';
			}

			if ( $value !== $last_value_to_prepare ) {
				// another item is coming...
				$post_content_where .= 'OR ';
				$postmeta_where .= 'OR ';
			}
		}

		$q = "SELECT * FROM {$wpdb->posts} {$trans_join}
		WHERE post_status = 'publish'
		AND post_type NOT IN ('revision')
		AND (
			ID IN (
				SELECT DISTINCT ID FROM {$wpdb->posts}
				WHERE ( {$post_content_where} )
				AND post_type NOT IN ('revision')
				AND post_status = 'publish'
			)
			OR ID IN (
				SELECT DISTINCT post_id FROM {$wpdb->postmeta}
				WHERE ( {$postmeta_where} )
			)
		)";

		$res = $wpdb->get_results(
			$wpdb->prepare(
				$q,
				array_merge( $flattened_values_to_prepare, $flattened_values_to_prepare )
			),
			OBJECT
		);

		$items = array();
		if ( ! empty( $res ) ) {
			foreach ( $res as $row ) {
				if ( $row->ID == $post_id ) {
					continue;
				}
				$language_flag = '';
				if( Toolset_WPML_Compatibility::get_instance()->is_wpml_active_and_configured() ) {
					if ( null !== $row->language_code ) {
						$language_code = $row->language_code;
					} else {
						$language_code = $sitepress->get_default_language();
					}
					$language_flag = $sitepress->get_flag_img( $language_code ) . ' ' ;
				}

				$type = get_post_type_object( $row->post_type );
				$type = $type->labels->singular_name;
				$view_link = '';
				if ( $row->post_type == 'view' ) {
					$view_settings = (array) get_post_meta( $row->ID, '_wpv_settings', true );
					/**
					 * exception for WordPress Archive
					 */
					if (
						isset( $view_settings['view-query-mode'] )
						&& in_array( $view_settings['view-query-mode'], array( 'archive', 'layouts-loop' ) )
					) {
						$type = __( 'WordPress Archive', 'wpv-views' );
						$edit_link = apply_filters(
							'wpv_filter_wpa_edit_link',
							get_admin_url() . "admin.php?page=view-archives-editor&view_id=" . $row->ID,
							$row->ID
						);
					} else {
						$edit_link = get_admin_url() . "admin.php?page=views-editor&view_id=" . $row->ID;
					}
				} else if( WPV_Content_Template_Embedded::POST_TYPE == $row->post_type ) {
					$edit_link = wpv_ct_editor_url( $row->ID );
				} else {
					$edit_link = get_admin_url() . "post.php?post=" . $row->ID . "&action=edit";
					$view_link = get_permalink( $row->ID );
					if( Toolset_WPML_Compatibility::get_instance()->is_wpml_active_and_configured() ) {
						$view_link = apply_filters( 'wpml_permalink', $view_link , $language_code );
					}
				}

				$items[] = array(
					'id'	=> $row->ID,
					'link'	=> $edit_link,
					'view'	=> $view_link,
					'title'	=> $language_flag . "<strong>" . $type . "</strong>: " . $row->post_title,
					'post_title'	=> $row->post_title,
					'post_type'	=> $type,
				);
				usort( $items, array( $this, 'view_usage_sort' ) );
			}
		}
		$data = array(
			'used_on' => $items
		);

		$this->ajax_finish( $data, true );
	}

	/**
	 * View Scan sort helper
	 *
	 * Sort items by two fields:
	 * - first by post type
	 * - second by post title
	 *
	 * @since unknown
	 */
	function view_usage_sort( $a, $b ) {
		if ( $a["post_type"] === $b["post_type"] ) {
			if ( $a["post_title"] === $b["post_title"] ) {
				return 0;
			}
			return ( $a["post_title"] < $b["post_title"] ) ? -1 : 1;
		}
		return ( $a["post_type"] < $b["post_type"] ) ? -1 : 1;
	}
}
