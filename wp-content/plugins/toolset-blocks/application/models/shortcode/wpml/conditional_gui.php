<?php

/**
 * wpml-conditional shortcode GUI
 *
 * @since 2.6.0
 */
class WPV_Shortcode_WPML_Conditional_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.6.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes[ WPV_Shortcode_WPML_Conditional::SHORTCODE_NAME ] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.6.0
	 */
	public function get_shortcode_data() {
		
		$options_languages = array();
		$active_languages = apply_filters( 'wpml_active_languages', array() );
		
		foreach ( $active_languages as $lang ) {
			$options_languages[ $lang['language_code'] ] = $lang['translated_name'];
		}
		
		$current_language = apply_filters( 'wpml_current_language', '' );
		
		$data = array(
			'name'           => __( 'Conditional output per language', 'wpv-views' ),
			'label'          => __( 'Conditional output per language', 'wpv-views' ),
			'post-selection' => true,
			'attributes'     => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'lang' => array(
							'label'         => __( 'Display content only for this language', 'wpv-views'),
							'type'          => 'select',
							'default_force' => $current_language,
							'options'       => $options_languages
						),
					),
					'content' => array(
						'label' => __( 'Conditional content', 'wpv-views' ),
						'type' => 'textarea',
						'description' => __( 'This will be displayed when the condition is met.', 'wpv-views' )
					)
				)
			)
		);
		return $data;
	}
	
	
}