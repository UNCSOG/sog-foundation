<?php

/**
 * Class WPV_Shortcode_Post_Comments_Number
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Comments_Number extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-comments-number';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'none'         => '',
		'one'          => '',
		'more'         => ''
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
	 * WPV_Shortcode_Post_Comments_Number constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct(
		Toolset_Shortcode_Attr_Interface $item
	) {
		$this->item  = $item;
		$this->shortcode_atts['none'] = __( 'No Comments', 'wpv-views' );
		$this->shortcode_atts['one']  = __( '1 Comment', 'wpv-views' );
		$this->shortcode_atts['more'] =  __( '% Comments', 'wpv-views' );
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
		
		if ( function_exists('icl_t') ) {
			if ( isset( $this->user_atts['none'] ) ) {
				icl_register_string('plugin Views', 'No comments-'.md5($this->user_atts['none']), $this->user_atts['none'] );
				$this->user_atts['none'] = icl_t('plugin Views', 'No comments-'.md5($this->user_atts['none']), $this->user_atts['none'] );
			}
			if ( isset( $this->user_atts['one'] ) ) {
				icl_register_string('plugin Views', 'One comment-'.md5($this->user_atts['one']), $this->user_atts['one'] );
				$this->user_atts['one'] = icl_t('plugin Views', 'One comment-'.md5($this->user_atts['one']), $this->user_atts['one'] );
			}
			if ( isset( $this->user_atts['more'] ) ) {
				icl_register_string('plugin Views', 'More comments-'.md5($this->user_atts['more']), $this->user_atts['more']);
				$this->user_atts['more'] = icl_t('plugin Views', 'More comments-'.md5($this->user_atts['more']), $this->user_atts['more'] );
			}
		}
		
		global $WPVDebug;
		
		$number = get_comments_number( $item->ID );
		
		if ( $number > 1 ) {
			$out .= str_replace( '%', number_format_i18n( $number ), $this->user_atts['more'] );
		} elseif ( $number == 0 ) {
			$out .= $this->user_atts['none'];
		} else { // must be one
			$out .= $this->user_atts['one'];
		}

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-comments-number', json_encode( $this->user_atts ), $WPVDebug->get_mysql_last(), 'Data received from cache', $out );

		return $out;
	}
}
