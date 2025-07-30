<?php

namespace OTGS\Toolset\Views\Controller\Compatibility;

use Toolset_Shortcode_Transformer;

/**
 * Handles the compatibility between Views and the Fusion Builder.
 */
class FusionBuilderCompatibility extends Base {

	/**
	 * Initializes the compatibility layer.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Set compatibility hooks.
	 */
	private function init_hooks() {
		add_filter( 'fusion_element_global_content', array( $this, 'adjust_alternative_syntax_on_global_elements' ), 0, 2 );
	}

	/**
	 * Make sure that shortcodes with alternative syntax get resolved in global elements.
	 *
	 * Since Fusion Builder became Avada Builder, global elements pass over wptexturize.
	 * That funcion just destroys attributes for alternative syntax shortcodes,
	 * hence we need to resolve them manually, even if that means recreating the whole outcome.
	 *
	 * @param string $content
	 * @param mixed[] $args
	 * @return string
	 */
	public function adjust_alternative_syntax_on_global_elements( $content, $args ) {
		if ( false === Toolset_Shortcode_Transformer::has_non_standard_syntax( $content, 'wpv' ) ) {
			return $content;
		}

		$global_element_id = toolset_getarr( $args, 'id' );
		if ( is_numeric( $global_element_id ) ) {
			// Get post contents.
			$global_element_post = get_post( $global_element_id );
			// Check if post exists.
			if ( ! is_null( $global_element_post ) ) {
				$global_element_content = apply_filters( 'toolset_transform_shortcode_format', $global_element_post->post_content );
				return do_shortcode( shortcode_unautop( wpautop( wptexturize( $global_element_content ) ) ) );
			}
		}

		return $content;
	}

}
