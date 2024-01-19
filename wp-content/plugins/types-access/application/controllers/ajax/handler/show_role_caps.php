<?php
/**
 * Class Access_Ajax_Handler_Show_Role_Caps
 * Show default role capabilities
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Show_Role_Caps extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Show_Role_Caps constructor.
	 *
	 * @param \OTGS\Toolset\Access\Ajax $access_ajax
	 */
	public function __construct( \OTGS\Toolset\Access\Ajax $access_ajax ) {
		parent::__construct( $access_ajax );
	}


	/**
	 * @param mixed $arguments
	 *
	 * @return array
	 */
	function process_call( $arguments ) {

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );
		$role = sanitize_text_field( $_POST['role'] );

		$out = '<form method="#">';
		$role_info = get_role( $role );
		$default_wordpress_caps = getDefaultWordpressCaps();
		$wocommerce_caps = get_woocommerce_caps();
		$wpml_caps_list = get_wpml_caps();
		$custom_caps = get_option( 'wpcf_access_custom_caps' );

		foreach ( $role_info->capabilities as $cap => $cap_info ) {
			if ( ! preg_match( "/level_[0-9]+/", $cap ) ) {
				$out .= '<p><label for="cap_'
					. esc_attr( $cap )
					. '"><input type="checkbox" checked="checked" value="" disabled id="cap_'
					. esc_attr( $cap )
					. '" >
			'
					. $cap;
				if ( isset( $default_wordpress_caps[ $cap ] ) ) {
					$out .= '<br><small>' . esc_html( $default_wordpress_caps[ $cap ] );
					if ( ! empty( $default_wordpress_caps[ $cap ][1] ) ) {
						$out .= ' (' . esc_html( $default_wordpress_caps[ $cap ] ) . ')';
					}
					if ( ! empty( $wocommerce_caps[ $cap ][1] ) ) {
						$out .= ' (' . esc_html( $wocommerce_caps[ $cap ][1] ) . ')';
					}
					if ( ! empty( $wpml_caps_list[ $cap ][1] ) ) {
						$out .= ' (' . esc_html( $wpml_caps_list[ $cap ][1] ) . ')';
					}

					$out .= '</small>';
				}
				if ( isset( $custom_caps[ $cap ] ) ) {
					$out .= '<br><small>' . esc_html( $custom_caps[ $cap ] ) . '</small>';
				}
				$out .= '</label></p>';
			}
		}

		$out .= '</form>';

		wp_send_json_success( $out );
	}
}
