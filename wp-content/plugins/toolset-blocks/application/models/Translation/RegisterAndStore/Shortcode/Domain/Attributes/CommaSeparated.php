<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsToPackageService;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Common\Domain\TranslationService;


/**
 * Class CommaSeparatedValue
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class CommaSeparated implements IAttribute {
	/** @var AttributeProperties */
	private $properties;

	/** @var TranslationService */
	private $translation_service;

	/**
	 * Views stores all content a couple of times in the post. As content, as block attribute...
	 * @var array
	 */
	private $already_translated = [];

	/**
	 * CommaSeparated constructor.
	 *
	 * @param AttributeProperties $properties
	 * @param TranslationService $translation_service
	 */
	public function __construct( AttributeProperties $properties, TranslationService $translation_service ) {
		$this->properties = $properties;
		$this->translation_service = $translation_service;
	}

	public function get_name() {
		return $this->properties->get_name();
	}

	/**
	 * @param string $shortcode
	 *
	 * @return array
	 */
	public function get_translatable_strings( $shortcode ) {
		$strings_to_translate = [];
		if( ! $values_array = $this->get_values( $shortcode ) ) {
			return $strings_to_translate;
		}

		foreach( $values_array as $value ) {
			$strings_to_translate[] = $this->translation_service->get_line_object(
				$value,
				$this->properties->get_title(),
				$this->properties->get_name()
			);
		}

		return $strings_to_translate;
	}

	public function apply_translation_to_post( \WP_Post $post, $original_shortcode_string, $packages ) {
		if( ! $original_values_array = $this->get_values( $original_shortcode_string ) ) {
			return;
		}

		if( in_array( md5( json_encode( $original_values_array ) ), $this->already_translated ) ) {
			return;
		}

		$translated_values_array = [];

		foreach( $packages as $package ) {
			foreach( $original_values_array as $original_value ) {
				if( ! isset( $package[ $original_value ] ) ) {
					break;
				}

				$translated_values_array[] = $package[ $original_value ];
			}

			if( count( $original_values_array ) === count( $translated_values_array ) ) {
				// All strings forund in the same package. No need to check further packages.
				break;
			}

			// None or just a few strings found. Means the wrong package was used. This is an edge case, having
			// the exact same strings on twice packages.
			$translated_values_array = [];
		}

		if( $translated_values_array ) {
			$post->post_content = str_replace(
				implode( ',', $original_values_array ),
				implode( ',', $translated_values_array ),
				$post->post_content
			);

			$this->already_translated[] = md5( json_encode( $original_values_array ) );
		}
	}

	private function get_values( $shortcode ) {
		if( ! preg_match( '#\[.*?'.$this->get_name() . '=([\'"])(.*?)\1.*?]#ism', $shortcode, $matches ) ) {
			// No values.
			return [];
		}

		// Remove extra spaces after comma.
		$values_without_spaces_after_comma = str_replace( ', ', ',', $matches[2] );
		return explode( ',', $values_without_spaces_after_comma );
	}
}
