<?php

/**
 * Class WPV_Shortcode_Post_Link
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Link extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-link';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'style'        => '',
		'class'        => '',
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
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	private $item;

	/**
	 * WPV_Shortcode_Post_Link constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct(
		Toolset_Shortcode_Attr_Interface $item
	) {
		$this->item = $item;
	}

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	*
	* @return string
	*
	* @since 2.5.0
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( ! $item_id = $this->item->get( $this->user_atts ) ) {
			// no valid item
			throw new WPV_Exception_Invalid_Shortcode_Attr_Item();
		}

		$out = '';

		$item = $this->get_post( $item_id );

		if ( null === $item ) {
			return $out;
		}

		$item_link = wpv_get_post_permalink( $item->ID );

		if ( false === $item_link ) {
			return $out;
		}

		$style = '';
		if ( ! empty( $this->user_atts['style'] ) ) {
			$style = ' style="'. esc_attr( $this->user_atts['style'] ) .'"';
		}

		$class = '';
		if ( ! empty( $this->user_atts['class'] ) ) {
			$class = ' class="' . esc_attr( $this->user_atts['class'] ) .'"';
		}

		$out .= '<a href="' . $item_link . '"'. $class . $style .'>';
		$out .= apply_filters( 'the_title', $item->post_title, $item->ID );
		$out .= '</a>';

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-link', json_encode( $this->user_atts ), '', 'Filter the_title applied', $out );

		return $out;
	}

}
