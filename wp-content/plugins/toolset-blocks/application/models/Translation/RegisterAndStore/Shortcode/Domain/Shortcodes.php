<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsToPackageService;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Common\Domain\TranslationService;

/**
 * Class Shortcodes
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class Shortcodes {
	/** @var Shortcode[] */
	private $shortcodes;

	/** @var TranslationService */
	private $translation_service;

	public function __construct( TranslationService $translation_service ) {
		$this->translation_service = $translation_service;
	}

	/**
	 * @param Shortcode $shortcode
	 */
	public function add( Shortcode $shortcode ) {
		$this->shortcodes[ $shortcode->get_slug() ] = $shortcode;
	}

	/**
	 * @param \WP_Post $post
	 */
	public function apply_shortcodes_to_package( \WP_Post $post ) {
		$strings_to_translate = [];
		foreach( $this->shortcodes as $shortcode ) {
			$strings_to_translate = array_merge(
				$strings_to_translate,
				$shortcode->get_translatable_strings( $post )
			);
		}

		$registered_strings = [];

		foreach( $strings_to_translate as $string_to_translate ) {
			if( in_array( $string_to_translate->id, $registered_strings ) ) {
				// Already registered.
				continue;
			}

			$this->translation_service->apply_string_to_shortcode_package( $string_to_translate, $post );
			$registered_strings[] = $string_to_translate->id;
		}
	}

	public function apply_translation_to_post( \WP_Post $post, $packages ) {
		foreach( $this->shortcodes as $shortcode ) {
			$shortcode->apply_translation_to_post( $post, $packages );
		}
	}
}
