<?php

/**
 * Class WPV_Shortcode_Post_Type
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Type implements WPV_Shortcode_Interface {

	const SHORTCODE_NAME = 'wpv-post-type';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'show'         => 'slug' // 'slug'|'single'|'plural'
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
	 * WPV_Shortcode_Post_Id constructor.
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

		$item = get_post( $item_id );

		if ( null === $item ) {
			return $out;
		}

		$item_object = get_post_type_object( $item->post_type );

		if ( null === $item_object ) {
			return $out;
		}

		switch ( $this->user_atts['show'] ) {
			case 'single':
				$out = $item_object->labels->singular_name;
				break;

			case 'plural':
				$out = $item_object->labels->name;
				break;

			case 'slug':
				$rewrite = $item_object->rewrite;
				$out = ( isset( $rewrite ) && isset( $rewrite['slug'] ) ) ? $rewrite['slug'] : $item->post_type;
				break;

			default:
				$out = $item->post_type;
				break;

		}

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-type', json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;
	}


}
