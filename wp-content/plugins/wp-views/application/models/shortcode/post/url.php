<?php

/**
 * Class WPV_Shortcode_Post_Url
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Url extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-url';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null // synonym for 'item'
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
	 * WPV_Shortcode_Post_Url constructor.
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

		$out = $item_link;

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-url', json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;
	}
}
