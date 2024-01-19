<?php

/**
 * Class WPV_Shortcode_Post_Author
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Author extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-author';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'format'       => 'name', // 'name'|'link'|'url'|'meta'
		'meta'         => 'nickname',
		'profile-picture-size'        => 96,
        'profile-picture-default-url' => '',
        'profile-picture-alt'         => false,
        'profile-picture-shape'       => 'circle' // 'circle'|'square'|'custom'
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
	 * WPV_Shortcode_Post_Author constructor.
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

		switch ( $this->user_atts['format'] ) {
			case 'link':
				$out = '<a href="' . esc_url( get_author_posts_url( $item->post_author ) ) . '">'
					. get_the_author_meta( 'display_name', $item->post_author )
					. '</a>';
				break;

			case 'url':
				$out = get_author_posts_url( $item->post_author );
				break;

			case 'meta':
				if ( in_array( $this->user_atts['meta'], array( 'id', 'ID' ) ) ) {
					$out = $item->post_author;
				} else {
					$out = get_the_author_meta( $this->user_atts['meta'], $item->post_author );
				}
				break;

			case 'profile_picture':
				$out = wpv_get_avatar(
					$item->post_author,
					$this->user_atts['profile-picture-size'],
					$this->user_atts['profile-picture-default-url'],
					$this->user_atts['profile-picture-alt'],
					$this->user_atts['profile-picture-shape']
				);
				break;

			default:
				$out = get_the_author_meta( 'display_name', $item->post_author );
				break;

		}
		apply_filters( 'wpv_shortcode_debug', 'wpv-post-author', json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;
	}
}
