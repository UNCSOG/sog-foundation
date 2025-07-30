<?php

/**
 * Main shortcodes GUI controller for Views.
 * 
 * @since 2.5.0
 */
final class WPV_Shortcodes_GUI {
	
	public function initialize() {
		
		add_action( 'wp_ajax_wpv_shortcode_gui_get_shortcode_data', array( $this, 'get_shortcode_data' ) );
		
	}
	
	public function get_shortcode_data() {
		
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wpv_editor_callback' ) ) {
			$data = array(
				'message' => __( 'Security: wrong nonce', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		
		if (
			! isset( $_GET['shortcode'] ) 
			|| empty( $_GET['shortcode'] ) 
		) {
			$data = array(
				'message' => __( 'Unknown shortcode', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		
		$shortcode		= sanitize_text_field( $_GET['shortcode'] );
		$parameters		= isset( $_GET['parameters'] ) ? $_GET['parameters'] : array();
		$overrides		= isset( $_GET['overrides'] ) ? $_GET['overrides'] : array();
		
		$parameters		= wpv_sanitize_shortcode_forced_data( $parameters );
		$overrides		= wpv_sanitize_shortcode_forced_data( $overrides );
		
		$gui_action		= isset( $_GET['gui_action'] ) ? sanitize_text_field( $_GET['gui_action'] ) : '';
		
		/**
		 * Get list of shortcodes with GUI data.
		 *
		 * @param array $views_shortcodes
		 *
		 * @since 1.9.0
		 */
		$views_shortcodes_gui_data = apply_filters( 'wpv_filter_wpv_shortcodes_gui_data', array() );
		
		if ( ! isset( $views_shortcodes_gui_data[ $shortcode ] ) ) {
			$data = array(
				'message' => __( 'Unknown shortcode', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		
		$shortcode_initial_data = $views_shortcodes_gui_data[ $shortcode ];
		
		if ( 
			isset( $shortcode_initial_data['callback'] )
			&& is_callable( $shortcode_initial_data['callback'] )
		) {
			$shortcode_data = call_user_func( $shortcode_initial_data['callback'], $parameters, $overrides );
		} else {
			$data = array(
				'message' => __( 'Unknown shortcode', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		
		$shortcode_data = $this->maybe_add_post_selection_options( $shortcode_data );
		$shortcode_data = $this->maybe_add_user_selection_options( $shortcode_data );
		
		wp_send_json_success( $shortcode_data );
		
	}
	
	private function maybe_add_post_selection_options( $shortcode_data ) {
		if ( 
			isset( $shortcode_data['post-selection'] ) 
			&& $shortcode_data['post-selection'] 
		) {
			if ( ! isset( $shortcode_data['attributes'] ) ) {
				$shortcode_data['attributes'] = array();
			}
			if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
				// m2m disabled
				$shortcode_data['attributes']['post-selection'] = array(
					'label'  => __( 'Post selection', 'wpv-views' ),
					'header' => __( 'Display data for:', 'wpv-views' ),
					'fields' => array(
						'id' => array(
							'type' => 'postLegacy'
						),
					),
				);
			} else {
				// m2m enabled
				$shortcode_data['attributes']['post-selection'] = array(
					'label'  => __( 'Post selection', 'wpv-views' ),
					'header' => __( 'Display data for:', 'wpv-views' ),
					'fields' => array(
						'item' => array(
							'type' => 'postSelection'
						),
					),
				);
			}
		}
		
		return $shortcode_data;
	}
	
	private function maybe_add_user_selection_options( $shortcode_data ) {
		if ( 
			isset( $shortcode_data['user-selection'] ) 
			&& $shortcode_data['user-selection'] 
		) {
			if ( ! isset( $shortcode_data['attributes'] ) ) {
				$shortcode_data['attributes'] = array();
			}
			$shortcode_data['attributes']['user-selection'] = array(
				'label'		=> __( 'User selection', 'wpv-views' ),
				'header'	=> __( 'Display data for:', 'wpv-views' ),
				'fields'	=> array(
					'id'	=> array(
						'type'	=> 'userSelection'
					),
				),
			);
		}
		
		return $shortcode_data;
	}
	
}