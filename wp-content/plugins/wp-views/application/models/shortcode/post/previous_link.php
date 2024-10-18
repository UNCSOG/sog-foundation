<?php

/**
 * Class WPV_Shortcode_Post_Previous_Link
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Previous_Link extends WPV_Shortcode_Post_Navigation_Link {

	const SHORTCODE_NAME = 'wpv-post-previous-link';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'format'       => '&laquo; %%LINK%%',
		'link'         => '%%TITLE%%',
		'wpml_context' => self::SHORTCODE_NAME,
	);


	/**
	 * Get the shortcode output value.
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return string
	 *
	 * @throws WPV_Exception_Invalid_Shortcode_Attr_Item
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

		$wpml_st_active = new Toolset_Condition_Plugin_Wpml_String_Translation_Is_Active();
		if ( $wpml_st_active->is_met() ) {
			$this->user_atts['format'] = $this->get_attribute_translation( 'post_control_for_previous_link_format', $this->user_atts['format'], $this->user_atts['wpml_context'] );
			$this->user_atts['link'] = $this->get_attribute_translation( 'post_control_for_previous_link_text', $this->user_atts['link'], $this->user_atts['wpml_context'] );
		}

		$processed_shortcode_placeholders = process_post_navigation_shortcode_placeholders( $this->user_atts['format'], $this->user_atts['link'] );
		$format = $processed_shortcode_placeholders['format'];
		$link = $processed_shortcode_placeholders['link'];
		
		global $post;
		$original_post = $post;
		$post = $item;

		$out .= get_previous_post_link( $format, $link );
		
		$post = $original_post;

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-previous-link', json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;
	}
}
