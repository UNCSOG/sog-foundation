<?php

namespace OTGS\Toolset\Views\Controller\Shortcode;

/**
 * Shotcode resolution controller.
 *
 * Views requires some unique shortcodes resolving methods:
 * - iterators.
 * - shortcodes in HTML attributes or as values for other shortcodes attributes.
 * - alternative syntax.
 * - formatting shortcodes.
 *
 * Gets instantiated and initialized on WPV_Main::initialize_classes at toolset_common_loaded:10.
 *
 * @since 1.9.1 Existing as a \WPV_Frontend_Render_Filters class.
 * @since 3.3.0 Ported to a proper controller.
 */
class Resolution {

	/**
	 * @var Resolver\Store
	 */
	private $store;

	/**
	 * @var \Toolset_Constants
	 */
	private $constants;

	/**
	 * Constructor.
	 *
	 * @param Resolver\Store $store
	 * @param \Toolset_Constants $constants
	 */
	public function __construct(
		Resolver\Store $store,
		\Toolset_Constants $constants
	) {
		$this->store = $store;
		$this->constants = $constants;
	}

	/**
	 * Initialize the controller.
	 *
	 * @since 3.3.0
	 */
	public function initialize() {
		add_filter( 'the_content', array( $this, 'pre_process_shortcodes' ), 5 );
		add_filter( 'wpv_filter_wpv_the_content_suppressed', array( $this, 'pre_process_shortcodes' ), 5 );
		add_filter( 'wpv-pre-do-shortcode', array( $this, 'pre_process_shortcodes' ), 5 );
		add_filter( 'wpv-pre-process-shortcodes', array( $this, 'pre_process_shortcodes' ), 5 );

		// This runs on toolset_common_loaded:10, so this constant is set.
		$tc_basic_formatting_hook = $this->constants->constant( '\OTGS\Toolset\Common\BasicFormatting::FILTER_NAME' );
		add_filter( $tc_basic_formatting_hook, array( $this, 'pre_process_shortcodes' ), 5 );

		add_filter( 'widget_text', array( $this, 'pre_process_widget_text' ), 9, 1 );
	}

	/**
	 * Preprocess shortcodes.
	 *
	 * Performs the following actions in the right order:
	 * - adjust alternative syntax.
	 * - resolve formatting shortodes.
	 * - resolve foreach shortcodes.
	 * - resolve shortcodes in shortcodes.
	 * - resolve conditional shortcodes, including legacy.
	 * - resolve shortcodes as HTML attributes.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	public function pre_process_shortcodes( $content ) {
		// Gutenberg, in particular, really loves calling this a lot with empty content, so this check saves a lot of
		// method calls that would do nothing.
		if ( empty( $content ) ) {
			return $content;
		}

		$resolvers = array(
			Resolver\AlternativeSyntax::SLUG,
			Resolver\Formatting::SLUG,
			Resolver\Iterators::SLUG,
			Resolver\Internals::SLUG,
			Resolver\Conditionals::SLUG,
			Resolver\HtmlAttributes::SLUG,
		);

		return $this->apply_resolvers( $content, $resolvers );
	}

	/**
	 * Preprocess shortcodes in the Text widget.
	 *
	 * Note that for historical reasons we only process internals and conditionals.
	 * Note that for historical reasons we process shortcodes again before returning.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	public function pre_process_widget_text( $content ) {
		// Gutenberg, in particular, really loves calling this a lot with empty content, so this check saves a lot of
		// method calls that would do nothing.
		if ( empty( $content ) ) {
			return $content;
		}

		$resolvers = array(
			Resolver\Internals::SLUG,
			Resolver\Conditionals::SLUG,
		);

		return do_shortcode( $this->apply_resolvers( $content, $resolvers ) );
	}

	/**
	 * Apply the desired resolvers.
	 *
	 * @param string $content
	 * @param array $resolvers
	 * @return string
	 */
	private function apply_resolvers( $content, $resolvers = array() ) {
		foreach ( $resolvers as $resolver ) {
			$resolver_object = $this->store->get_resolver( $resolver );
			if ( null !== $resolver_object ) {
				$content = $resolver_object->apply_resolver( $content );
			}
		}

		return $content;
	}
}
