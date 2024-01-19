<?php

/**
 * Class WPV_Shortcode_Post_Class
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Class extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-class';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'add'          => ''
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
	 * WPV_Shortcode_Post_Class constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct(
		Toolset_Shortcode_Attr_Interface $item
	) {
		$this->item  = $item;
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

		// get_post_class handles the escaping of the additional classnames.
		// We need to force the $post->post_type classname as it is added in the frontend 
		// but in AJAX it is not included as it is considered backend.
		$added_classnames = array();
		if ( ! empty( $this->user_atts['add'] ) ) {
			$added_classnames = explode( ' ', $this->user_atts['add'] );
		}
		$added_classnames[] = $item->post_type;

		$item_classes = get_post_class( $added_classnames, $item->ID );
		$out .= implode( ' ', $item_classes );

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-class', json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;
	}
}
