<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Application;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\Factory\ShortcodesFactory;

/**
 * Class StringsToPostService
 *
 * Apply translated strings.
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Application
 * @codeCoverageIgnore No need to test this service.
 *
 * @since TB 1.3
 */
class StringsToPostService {
	/** @var ShortcodesFactory */
	private $shortcodes_factory;

	/**
	 * StringsToPackageService constructor.
	 *
	 * @param ShortcodesFactory $shortcodes_factory
	 */
	public function __construct(
		ShortcodesFactory $shortcodes_factory
	) {
		$this->shortcodes_factory = $shortcodes_factory;
	}

	/**
	 *
	 * @param \WP_Post $post
	 * @param array $packages
	 */
	public function execute( $post, $packages ) {
		$shortcodes = $this->shortcodes_factory->get_shortcodes();

		$shortcodes->apply_translation_to_post( $post, $packages );

		wp_update_post( $post );
	}
}
