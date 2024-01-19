<?php

namespace OTGS\Toolset\Views\Controller\Shortcode\Resolver;

/**
 * Shotcode resolver controller: formatting shortcodes.
 *
 * @since 3.3.0
 */
class Conditionals implements IResolver {

	const SLUG = 'conditionals';

	/**
	 * @var \Toolset_Constants
	 */
	private $constants;

	/**
	 * Constructor.
	 *
	 * @param \Toolset_Constants $constants
	 */
	public function __construct(
		\Toolset_Constants $constants
	) {
		$this->constants = $constants;
	}

	/**
	 * Apply resolver.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	public function apply_resolver( $content ) {
		$conditional_shortcode = $this->constants->constant( '\WPV_Views_Conditional::SHORTCODE_NAME' );
		if ( false !== strpos( $content, '[' . $conditional_shortcode ) ) {
			$content = $this->process_conditional_shortcodes( $content );
		}

		// @codeCoverageIgnoreStart
		if ( false !== strpos( $content, '[wpv-if' ) ) {
			$content = $this->process_legacy_conditional_shortcodes( $content );
		}
		// @codeCoverageIgnoreEnd

		return $content;
	}

	/**
	 * Process \WPV_Views_Conditional::SHORTCODE_NAME shortcodes.
	 *
	 * This offloads all the logic to \WPV_Views_Conditional::process_conditional_shortcodes.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	private function process_conditional_shortcodes( $content ) {
		return apply_filters( 'wpv_process_conditional_shortcodes', $content );
	}

	/**
	 * Process wpv-if legacy shortcodes.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 * @codeCoverageIgnore
	 */
	private function process_legacy_conditional_shortcodes( $content ) {
		$content = wpv_resolve_wpv_if_shortcodes( $content );

		return $content;
	}

}
