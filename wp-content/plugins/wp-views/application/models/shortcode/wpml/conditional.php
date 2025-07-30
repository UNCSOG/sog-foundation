<?php

/**
 * wpml-conditional shortcode management
 *
 * Displays content conditionally based on the current language.
 *
 * @since 2.6.0
 */
class WPV_Shortcode_WPML_Conditional implements WPV_Shortcode_Interface, WPV_Shortcode_Interface_Conditional {

	const SHORTCODE_NAME = 'wpml-conditional';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'lang' => ''
	);

	/**
	 * @var string|null
	 */
	private $user_content;

	/**
	 * @var array
	 */
	private $user_atts;

	/**
	 * @return bool
	 *
	 * @since 2.6.0
	 */
	public function condition_is_met() {
		return apply_filters( 'toolset_is_wpml_active_and_configured', false );
	}

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	*
	* @return string
	*
	* @since 2.6.0
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( empty( $this->user_atts['lang'] ) ) {
			return '';
		}

		$current_language = apply_filters( 'wpml_current_language', '' );

		if ( $current_language === $this->user_atts['lang'] ) {
			return wpv_do_shortcode( $this->user_content );
		}

		return '';
	}

}
