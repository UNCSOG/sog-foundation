<?php

namespace OTGS\Toolset\Views\Models\Shortcode\Taxonomy;

/**
 * Taxonomy archive shortcode.
 *
 * @since 3.0.1
 */
class Archive extends \WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-taxonomy-archive';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'info' => 'name',
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
	 * Get the current term from the term archive environment.
	 *
	 * Note that this can come from the current queried object,
	 * but also from the POSTed data of a View or WPA AJAX operation.
	 *
	 * @return \WP_Term|null
	 */
	private function get_current_archive_term() {
		$archive_environment = apply_filters( 'wpv_filter_wpv_get_current_archive_loop', array() );
		if (
			isset( $archive_environment['type'] )
			&& 'taxonomy' === $archive_environment['type']
			&& isset( $archive_environment['data']['term_id'] )
		) {
			return get_term( $archive_environment['data']['term_id'] );
		}

		$queried_object = get_queried_object();
		if ( $queried_object instanceof \WP_Term ) {
			return $queried_object;
		}

		return null;
	}

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	* @return string
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		$current_term = $this->get_current_archive_term();
		$out = '';

		if ( null === $current_term ) {
			return $out;
		}

		switch ( $this->user_atts['info'] ) {
			case 'name':
				$out = $current_term->name;
				break;
			case 'slug':
				$out = $current_term->slug;
				break;
			case 'description':
				$out = $current_term->description;
				break;
			case 'id':
				$out = $current_term->term_taxonomy_id;
				break;
			case 'taxonomy':
				$out = $current_term->taxonomy;
				break;
			case 'parent':
				$out = $current_term->parent;
				break;
			case 'count':
				$out = $current_term->count;
				break;
		}

		apply_filters( 'wpv_shortcode_debug','wpv-taxonomy-archive', json_encode( $this->user_atts ), '', 'Data received from cache.', $out );

		return $out;
	}

}
