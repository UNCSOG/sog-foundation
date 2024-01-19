<?php

namespace OTGS\Toolset\Views\Models\Shortcode\Post;

/**
 * Taxonomy iterator shortcode.
 *
 * @since 3.0.1
 */
class Taxonomy_Iterator extends \WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-taxonomy-iterator';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item' => null, // post
		'id' => null, // synonym for 'item'
		'post_id' => null, // synonym for 'item'
		'taxonomy' => '',
		'separator' => '',
		'orderby' => '',
		'order' => '',
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
	 * @var \Toolset_Shortcode_Attr_Interface
	 */
	private $item;

	/**
	 * Constructor.
	 *
	 * @param \Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct(
		\Toolset_Shortcode_Attr_Interface $item
	) {
		$this->item  = $item;
	}

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	* @return string
	* @since 2.5.0
	* @since 2.8.1 Added the separator attribute.
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( empty( $this->user_atts['taxonomy'] ) ) {
			return '';
		}

		if ( ! $item_id = $this->item->get( $this->user_atts ) ) {
			// no valid item
			throw new \WPV_Exception_Invalid_Shortcode_Attr_Item();
		}

		$item = $this->get_post( $item_id );

		if ( null === $item ) {
			return '';
		}

		$terms = get_the_terms( $item, $this->user_atts['taxonomy'] );

		if (
			false === $terms
			|| is_wp_error( $terms )
		) {
			return '';
		}

		if ( strpos( $this->user_content, 'wpv-b64-' ) === 0 ) {
			$this->user_content = substr( $this->user_content, 7 );
			$this->user_content = base64_decode( $this->user_content );
		}

		$inner_types_loopers = "/\\[(types).*?\\]/i";
		$types_counts = preg_match_all( $inner_types_loopers, $this->user_content, $types_matches );

		$inner_views_loopers = "/\\[(" . implode( '|', \WPV_Shortcodes::TAXONOMY_SHORTCODES ) . ").*?\\]/i";
		$views_counts = preg_match_all( $inner_views_loopers, $this->user_content, $views_matches );

		$value_arr = array();

		foreach ( $terms as $term ) {
			$new_value = $this->user_content;
			if ( $types_counts > 0 ) {
				foreach( $types_matches[0] as $index => $match ) {
					// execute shortcode content and replace
					$shortcode = $types_matches[ 1 ][ $index ];
					$resolved_match = $match;

					$apply_index = $this->should_apply_term_id( $shortcode, $match );
					if ( $apply_index ) {
						$resolved_match = str_replace( '[' . $shortcode . ' ', '[' . $shortcode . ' term_id="' . $term->term_id . '" ', $resolved_match );
					}

					$new_value = str_replace( $match, $resolved_match, $new_value );
				}
			}
			if ( $views_counts > 0 ) {
				foreach( $views_matches[0] as $index => $match ) {
					// execute shortcode content and replace
					$shortcode = $views_matches[ 1 ][ $index ];
					$resolved_match = $match;

					$apply_index = $this->should_apply_term_id( $shortcode, $match );
					if ( $apply_index ) {
						$resolved_match = str_replace( '[' . $shortcode, '[' . $shortcode . ' term_id="' . $term->term_id . '"', $resolved_match );
					}

					$new_value = str_replace( $match, $resolved_match, $new_value );
				}
			}
			$value_arr[] = $new_value;
		}

		$out = implode( $this->user_atts['separator'], $value_arr );

		apply_filters( 'wpv_shortcode_debug', self::SHORTCODE_NAME, json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;

	}

	/**
	 * Decide whether the curret shortcode needs to get the term_id attribute added.
	 *
	 * @param string The shortcode handle
	 * @param string The shortcode string
	 * @return bool
	 * @since 3.0.1
	 */
	private function should_apply_term_id( $shortcode_type, $shortcode ) {
		if ( strpos( $shortcode, " term_id=" ) !== false ) {
			return false;
		}

		if (
			'types' === $shortcode_type
			&& strpos( $shortcode, " termmeta=" ) === false
		) {
			return false;
		}

		return true;
	}

}
