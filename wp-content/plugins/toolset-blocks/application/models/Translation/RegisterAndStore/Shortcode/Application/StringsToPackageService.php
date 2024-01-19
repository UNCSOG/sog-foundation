<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Application;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\Factory\ShortcodesFactory;

/**
 * Class StringsToPackageService
 *
 * Send strings to WPML.
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Application
 * @codeCoverageIgnore No need to test this service.
 *
 * @since TB 1.3
 */
class StringsToPackageService {
	/** @var ShortcodesFactory */
	private $shortcodes_factory;

	/**
	 * StringsToPackageService constructor.
	 *
	 * @param ShortcodesFactory $shortcodes_factory
	 */
	public function __construct( ShortcodesFactory $shortcodes_factory ) {
		$this->shortcodes_factory = $shortcodes_factory;
	}

	/**
	 *
	 * @param \WP_Post|null $post
	 *
	 */
	public function execute( \WP_Post $post ) {
		$shortcodes = $this->shortcodes_factory->get_shortcodes();

		$shortcodes->apply_shortcodes_to_package( $post );
	}
}
