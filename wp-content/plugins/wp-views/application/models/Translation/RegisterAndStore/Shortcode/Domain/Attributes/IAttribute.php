<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsForTranslationService;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsToPackageService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Interface IAttribute
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
interface IAttribute {
	/**
	 * Attribute slug.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Returns strings to translate. Normally these strings are more complex than just passing the complete
	 * value as a single translatable item (because just strings can simply be added by the wpml-config.xml).
	 *
	 * @param string $shortcode_string
	 *
	 * @return array
	 */
	public function get_translatable_strings( $shortcode_string );


	public function apply_translation_to_post( \WP_Post $post, $original_shortcode_string, $packages );
}
