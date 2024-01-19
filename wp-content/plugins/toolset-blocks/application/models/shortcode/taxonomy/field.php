<?php

namespace OTGS\Toolset\Views\Models\Shortcode\Taxonomy;

/**
 * Taxonomy term field shortcode.
 *
 * @since 3.0.1
 */
class Field extends \WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-taxonomy-field';

	/**
	 * @var \OTGS\Toolset\Common\Wordpress\Version
	 */
	private $wp_version;

	public function __construct( \OTGS\Toolset\Common\Wordpress\Version $wp_version = null ) {
		$this->wp_version = ( null === $wp_version )
			? new \OTGS\Toolset\Common\Wordpress\Version()
			: $wp_version;
	}

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'term_id' => null,
		'index' => '',
		'name' => '',
		'separator' => ', '
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
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	* @return string
	* @since 3.0.1
	*/
	public function get_value( $atts, $content = null ) {
		if ( $this->wp_version->compare( '4.4' ) < 0 ) {
			return '';
		}

		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( '' === $this->user_atts['name'] ) {
			return '';
		}

		$term = $this->get_term( $this->user_atts['term_id'] );

		if ( null === $term ) {
			return '';
		}

		$out = '';
		$filters = '';

		$meta = get_term_meta( $term->term_id, $this->user_atts['name'] );
		$meta = apply_filters( 'wpv-taxonomy-field-meta-' . $this->user_atts['name'], $meta );
		$filters .= 'Filter wpv-taxonomy-field-meta-' . $this->user_atts['name'] .' applied. ';
		if ( $meta ) {
			if ( $this->user_atts['index'] !== '' ) {
				$index = intval( $this->user_atts['index'] );
				$filters .= 'displaying index ' . $index . '. ';
				$out .= toolset_getarr( $meta, $index, '' );
			} else {
				$filters .= 'no index set. ';
				foreach ( $meta as $item ) {
					if ( $out != '' ) {
						$out .= $this->user_atts['separator'];
					}
					$out .= $item;
				}
			}
		}

		$out = apply_filters( 'wpv-taxonomy-field-' . $this->user_atts['name'], $out, $meta );
		$filters .= 'Filter wpv-taxonomy-field-' . $this->user_atts['name'] . ' applied. ';

		apply_filters( 'wpv_shortcode_debug', self::SHORTCODE_NAME, json_encode( $this->user_atts ), '', 'Data received from cache. '. $filters, $out );

		return $out;
	}

}
