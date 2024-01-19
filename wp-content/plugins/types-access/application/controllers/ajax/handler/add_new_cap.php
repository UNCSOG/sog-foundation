<?php
/**
 * Class Access_Ajax_Handler_Add_New_Cap
 * Add new custom capability
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Add_New_Cap extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Add_New_Cap constructor.
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

		$custom_caps = get_option( 'wpcf_access_custom_caps' );

		if ( ! is_array( $custom_caps ) ) {
			$custom_caps = array();
		}

		$default_wordpress_caps = getDefaultWordpressCaps();
		$wocommerce_caps = get_woocommerce_caps();
		$wpml_caps_list = get_wpml_caps();
		$cap = sanitize_text_field( $_POST['cap_name'] );
		$description = sanitize_text_field( $_POST['cap_description'] );

		if ( isset( $custom_caps[ $cap ] )
			|| isset( $default_wordpress_caps[ $cap ] )
			|| isset( $wocommerce_caps[ $cap ] )
			|| isset( $wpml_caps_list[ $cap ] ) ) {
			$output = array( 'error', __( 'This capability already exists in your site', 'wpcf-access' ) );
		} else {
			$custom_caps[ $cap ] = $description;
			update_option( 'wpcf_access_custom_caps', $custom_caps );
			$attr_name = esc_attr( $cap );
			$input = '<p id="wpcf-custom-cap-'
				. $attr_name
				. '"><label for="cap_'
				. $attr_name
				. '"><input type="checkbox" name="current_role_caps[]" value="Access:cap_'
				. $attr_name
				. '" id="cap_'
				. $attr_name
				. '" checked="checked">
				'
				. $attr_name
				. '<br><small>'
				. $description
				. '</small></label>'
				.
				'<span class="js-wpcf-remove-custom-cap js-wpcf-remove-custom-cap_'
				. $attr_name
				. '">'
				.
				'<a href="" data-object="wpcf-custom-cap-'
				. $attr_name
				. '" data-remove="0" data-cap="'
				. $attr_name
				. '">Delete</a><span class="ajax-loading spinner"></span>'
				.
				'</span>'
				.
				'</p>';
			$output = array( 1, $input );
		}
		wp_send_json_success( $output );

	}
}
