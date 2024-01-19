<?php

/**
 * Class WPV_Shortcode_Post_Field
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Field extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-field';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'index'        => '',
		'name'         => '',
		'separator'    => ', ',
		'parse_shortcodes' => ''
	);
	
	/**
	 * @var array
	 */
	private $infinite_loop_keys = array();

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
	 * WPV_Shortcode_Post_Field constructor.
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
		
		$out = '';
		
		// When the $name is empty, we should return an empty string, as the "get_post_meta" function will
		// return data for all keys, which is not helpful.
		if ( empty( $this->user_atts['name'] ) ) {
			return $out;
		}

		if ( ! $item_id = $this->item->get( $this->user_atts ) ) {
			// no valid item
			throw new WPV_Exception_Invalid_Shortcode_Attr_Item();
		}

		$item = $this->get_post( $item_id );

		if ( null === $item ) {
			return $out;
		}

		$filters_applied = '';

		$meta = get_post_meta( $item->ID, $this->user_atts['name'] );
		$meta = apply_filters('wpv-post-field-meta-' . $this->user_atts['name'], $meta);
		$filters_applied .= 'Filter wpv-post-field-meta-' . $this->user_atts['name'] .' applied. ';
		
		if ( $meta ) {
			if ( '' !== $this->user_atts['index'] ) {
				// We do nto check against empty because 0 is a valid index and is indeed empty
				$index = intval( $this->user_atts['index'] );
				$filters_applied .= 'Displaying index ' . $index . '. ';
				$out .= isset( $meta[ $index ] ) ? $meta[ $index ] : '';
			} else {
				$filters_applied .= 'No index set. ';
				foreach( $meta as $meta_item ) {
					if ( $out != '' ) {
						$out .= $this->user_atts['separator'];
					}
					$out .= wpv_maybe_flatten_array( $meta_item, $this->user_atts['separator'] );
				}

			}
		}

		$out = apply_filters('wpv-post-field-' . $this->user_atts['name'], $out, $meta);
		$filters_applied .= 'Filter wpv-post-field-' . $this->user_atts['name'] . ' applied. ';

		if ( 
			$this->user_atts['parse_shortcodes'] == 'true' 
			|| $this->user_atts['parse_shortcodes'] == 1 
		) {
			if ( isset( $this->infinite_loop_keys[ $item->ID . '-' . $this->user_atts['name'] ] ) ) {
				return '';
			}
			$this->infinite_loop_keys[ $item->ID . '-' . $this->user_atts['name'] ] = true;
			$out = wpv_do_shortcode( $out );
			unset( $this->infinite_loop_keys[ $item->ID . '-' . $this->user_atts['name'] ] );
		}

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-field', json_encode( $this->user_atts ), '', 'Data received from cache. ' . $filters_applied, $out );

		return $out;
	}
}
