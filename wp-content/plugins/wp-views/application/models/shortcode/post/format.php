<?php

/**
 * Class WPV_Shortcode_Post_Format
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Format extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-format';

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
	 * WPV_Shortcode_Post_Format constructor.
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

		$item_format = get_post_format( $item->ID );
        if ( $item_format !== false ) {
            $out .= $item_format;
        } else {
            $out .= 'standard';
        }

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-format', json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;
	}
}
