<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsToPackageService;


/**
 * Class Attributes
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class Attributes {
	/** @var IAttribute[] */
	private $attributes = [];

	/**
	 * @param IAttribute $attribute
	 */
	public function add( IAttribute $attribute ) {
		$this->attributes[ $attribute->get_name() ] = $attribute;
	}

	public function get_translatable_strings( $shortcode_string ) {
		$strings_to_translate = [];
		foreach( $this->attributes as $attribute ) {
			$strings_to_translate = array_merge(
				$strings_to_translate,
				$attribute->get_translatable_strings( $shortcode_string )
			);
		}

		return $strings_to_translate;
	}

	public function apply_translation_to_post( \WP_Post $post, $original_shortcode_string, $packages) {
		foreach( $this->attributes as $attribute ) {
			$attribute->apply_translation_to_post( $post, $original_shortcode_string, $packages );
		}
	}
}
