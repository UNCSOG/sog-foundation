<?php

/**
 * class WPV_Shortcode_Base_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Base_GUI implements WPV_Shortcode_Interface_GUI  {

	/**
	 * WPV_Shortcode_Base_GUI constructor.
	 */
	public function __construct() {
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data', array( $this, 'register_shortcode_data' ) );

		add_filter( 'wpv_filter_wpv_shortcodes_gui_wpml_context_data', array( $this, 'get_wpml_context_field_data' ), 10, 2 );
	}

	/**
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		return $views_shortcodes;
	}

	/**
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		return;
	}

	/**
	 * @return array
	 *
	 * @since 2.7.0
	 */
	public function get_wpml_context_field_data( $shortcode_data = array(), $placeholer = '' ) {
		$wpml_st_active = new Toolset_Condition_Plugin_Wpml_String_Translation_Is_Active();
		if (
			$wpml_st_active->is_met() &&
			isset( $shortcode_data['attributes']['display-options']['fields'] ) &&
			is_array( $shortcode_data['attributes']['display-options']['fields'] )
		) {
			$shortcode_data['attributes']['display-options']['fields']['wpml_context'] = array(
				'label'			=> __( 'WPML Translation Context', 'wpv-views' ),
				'type'			=> 'text',
				/* translators: Description for the context attribute on the GUI for the shortcode to register strings for translation */
				'description'	=> __( 'This context will be used when registering shortcode attributes for translation in WPML String Translation.', 'wpv-views' ),
				'placeholder'	=> $placeholer,
			);
		}

		return $shortcode_data;
	}
}
