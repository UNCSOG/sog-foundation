<?php

/**
 * Class WPV_Shortcode_Post_Taxonomy
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Taxonomy extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-taxonomy';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'      => null, // post
		'id'        => null, // synonym for 'item'
		'post_id'   => null, // synonym for 'item'
		'type'      => '',
		'separator' => ', ',
		'format'    => 'link',
		'show'      => 'name',
		'order'     => 'asc'
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
	 * WPV_Shortcode_Post_Taxonomy constructor.
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

		if ( empty( $this->user_atts['type'] ) ) {
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

		$types = explode( ',', $this->user_atts['type'] );
		if ( empty( $types ) ) {
			return $out;
		} else {
			$types = array_map( 'trim', $types );
			$types = array_map( 'sanitize_text_field', $types );
		}

		$out_terms = array();
		foreach ( $types as $taxonomy_slug ) {
			$terms = get_the_terms( $item->ID, $taxonomy_slug );
			if (
				$terms
				&& ! is_wp_error( $terms )
			) {
				foreach ( $terms as $term ) {
					// Adjust the term in case WPML is not set to auto-adjust IDs.
					$term = get_term( apply_filters( 'wpml_object_id', $term->term_id, $taxonomy_slug, true ) );
					// Check whether the filter and the core function return the right object type.
					if ( ! $term instanceof WP_Term ) {
						continue;
					}
					switch ( strtolower( $this->user_atts['format'] ) ) {
						case 'text':// DEPRECATED at 1.9, keep for backwards compatibility
							$text = $term->name;
							switch ( $this->user_atts['show'] ) {
								case 'description':
									$text = $term->description;
									break;
								case 'count':
									$text = $term->count;
									break;
								case 'slug':
									$text = $term->slug;
									break;
							}
							$out_terms[ $term->name ] = $text;
							break;
						case 'name':
							$out_terms[ $term->name ] = $term->name;
							break;
						case 'description':
							$out_terms[ $term->name ] = $term->description;
							break;
						case 'count':
							$out_terms[ $term->name ] = $term->count;
							break;
						case 'slug':
							$out_terms[ $term->name ] = urldecode( $term->slug );
							break;
						case 'url':
							$term_link = get_term_link( $term, $taxonomy_slug );
							$out_terms[ $term->name ] = $term_link;
							break;
						case 'id':
							$out_terms[ $term->name ] = $term->term_id;
							break;
						default:
							$term_link = get_term_link( $term, $taxonomy_slug );
							$text = $term->name;
							switch ( $this->user_atts['show'] ) {
								case 'description':
									$text = $term->description;
									break;
								case 'count':
									$text = $term->count;
									break;
								case 'slug':
									$text = $term->slug;
									break;
							}
							$out_terms[ $term->name ] = '<a href="' . $term_link . '">' . $text . '</a>';
							break;
					}
				}
			}
		}
		if ( ! empty( $out_terms ) ) {
			if ( $this->user_atts['order'] == 'asc' ) {
				ksort( $out_terms );
			} elseif ( $this->user_atts['order'] == 'desc' ) {
				ksort( $out_terms );
				$out_terms = array_reverse( $out_terms );
			}
			$out = implode( $this->user_atts['separator'], $out_terms );
		}

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-taxonomy', json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;
	}
}
