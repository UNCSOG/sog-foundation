<?php

/**
 * Class WPV_Shortcode_Post_Read_More
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Read_More extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-read-more';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item' => null, // post
		'id' => null, // synonym for 'item'
		'post_id' => null, // synonym for 'item'
		'label' => '',
		'wpml_context' => self::SHORTCODE_NAME,
		'style' => '',
		'class' => '',
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
	 * WPV_Shortcode_Post_Read_More constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct(
		Toolset_Shortcode_Attr_Interface $item
	) {
		$this->item = $item;
		$this->shortcode_atts['label'] = __( 'Read more', 'wpv-views' );
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
		$out .= $this->get_link_handle( $item );
		$out .= '</a>';

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-read-more', json_encode( $this->user_atts ), '', 'Filter the_title applied', $out );

		return $out;
	}

	/**
	 * Get the link handle, replacing the following placeholders:
	 * - %%TITLE%% by the post title
	 *
	 * @param WP_Post $item
	 * @return string
	 * @since 2.8
	 */
	private function get_link_handle( $item ) {
		if ( __( 'Read more', 'wpv-views' ) === $this->user_atts['label'] ) {
			return $this->user_atts['label'];
		}

		if ( '%%TITLE%%' === $this->user_atts['label'] ) {
			return $this->get_item_title( $item );
		}

		$wpml_st_active = new Toolset_Condition_Plugin_Wpml_String_Translation_Is_Active();
		if ( $wpml_st_active->is_met() ) {
			$this->user_atts['label'] = $this->get_translation(
				self::SHORTCODE_NAME . '_' . md5( $this->user_atts['label'] ),
				$this->user_atts['label'],
				$this->user_atts['wpml_context']
			);
		}

		if ( false === strpos( $this->user_atts['label'], '%%TITLE%%' ) ) {
			return $this->user_atts['label'];
		}

		return str_replace( '%%TITLE%%', $this->get_item_title( $item ), $this->user_atts['label'] );
	}

	/**
	 * Get the title of the relevant post.
	 *
	 * @param WP_Post $item
	 * @return string
	 * @since 2.8
	 */
	private function get_item_title( $item ) {
		return apply_filters( 'the_title', $item->post_title, $item->ID );
	}
}
