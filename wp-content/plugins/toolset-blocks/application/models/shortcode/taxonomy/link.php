<?php

namespace OTGS\Toolset\Views\Models\Shortcode\Taxonomy;

/**
 * Taxonomy term link shortcode.
 *
 * @since 3.0.1
 */
class Link extends \WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-taxonomy-link';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'term_id' => null,
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
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	* @return string
	* @since 3.0.1
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		$term = $this->get_term( $this->user_atts['term_id'] );

		if ( null === $term ) {
			return '';
		}

		$out = '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';

		apply_filters( 'wpv_shortcode_debug', self::SHORTCODE_NAME, json_encode( $this->user_atts ), '', 'Data received from cache.', $out );

		return $out;
	}

}
