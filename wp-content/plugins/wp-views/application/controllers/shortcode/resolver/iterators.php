<?php

namespace OTGS\Toolset\Views\Controller\Shortcode\Resolver;

/**
 * Shotcode resolver controller: iterators.
 *
 * Note that the content of the iterator shortcode is base64-encoded,
 * so WordPress does not process it. The loop iterator shortcode should
 * decode its own content and adjust it properly.
 *
 * @since 3.3.0
 */
class Iterators implements IResolver {

	const SLUG = 'iterators';

	/**
	 * @var \Toolset_Constants
	 */
	private $constants;

	/**
	 * @var \WPV_Shortcode_Factory
	 */
	private $shortcode_factory;

	/**
	 * Constructor.
	 *
	 * @param \Toolset_Constants $constants
	 * @param \WPV_Shortcode_Factory $shortcode_factory
	 */
	public function __construct(
		\Toolset_Constants $constants,
		$shortcode_factory = null
	) {
		$this->constants = $constants;
		$this->shortcode_factory = $shortcode_factory;
	}

	/**
	 * Apply resolver.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	public function apply_resolver( $content ) {
		$iterator_shortcodes = array();

		$iterator_shortcodes[] = $this->constants->constant( '\WPV_Shortcode_Post_Field_Iterator::SHORTCODE_NAME' );
		$iterator_shortcodes[] = $this->constants->constant( '\WPV_Shortcode_Post_Field_Iterator::SHORTCODE_NAME_ALIAS' );
		$iterator_shortcodes[] = $this->constants->constant( '\OTGS\Toolset\Views\Models\Shortcode\Post\Taxonomy_Iterator::SHORTCODE_NAME' );

		foreach ( $iterator_shortcodes as $shortcode ) {
			$content = $this->process_iterator_shortcode( $shortcode, $content );
		}

		return $content;
	}

	/**
	 * Get the factory to generate shortcode callbacks.
	 *
	 * @return \WPV_Shortcode_Factory
	 * @since 3.3.0
	 */
	private function get_shortcode_factory() {
		if ( $this->shortcode_factory instanceof \WPV_Shortcode_Factory ) {
			return $this->shortcode_factory;
		}

		// @codeCoverageIgnoreStart
		$relationship_service = new \Toolset_Relationship_Service();
		$attr_item_chain = new \Toolset_Shortcode_Attr_Item_M2M(
			new \Toolset_Shortcode_Attr_Item_Legacy(
				new \Toolset_Shortcode_Attr_Item_Id(),
				$relationship_service
			),
			$relationship_service
		);

		$this->shortcode_factory = new \WPV_Shortcode_Factory( $attr_item_chain );

		return $this->shortcode_factory;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Process a single iterator shortcode.
	 *
	 * @param string $shortcode
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	private function process_iterator_shortcode( $shortcode, $content ) {
		if ( false === strpos( $content, '[' . $shortcode ) ) {
			return $content;
		}

		global $shortcode_tags;
		// Back up current registered shortcodes and clear them all out.
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		$shortcode_object = $this->get_shortcode_factory()->get_shortcode( $shortcode );
		// @codeCoverageIgnoreStart
		if ( $shortcode_object ) {
			add_shortcode( $shortcode, array( $shortcode_object, 'render' ) );
		}
		// @codeCoverageIgnoreEnd

		$expression = '/\\[' . $shortcode . '.*?\\](.*?)\\[\\/' . $shortcode . '\\]/is';
		$counts = preg_match_all( $expression, $content, $matches );
		while ( $counts ) {
			foreach ( $matches[0] as $index => $match ) {
				// Encode the data to stop WP from trying to fix or parse it.
				// The iterator shortcode will manage this on render.
				// @codingStandardsIgnoreLine
				$match_encoded = str_replace( $matches[1][ $index ], 'wpv-b64-' . base64_encode( $matches[ 1 ][ $index ] ), $match );
				$shortcode = do_shortcode( $match_encoded );
				$content = str_replace( $match, $shortcode, $content );
			}
			$counts = preg_match_all( $expression, $content, $matches );
		}

		// @codingStandardsIgnoreLine
		$shortcode_tags = $orig_shortcode_tags;

		return $content;
	}

}
