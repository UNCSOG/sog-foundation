<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Common\Domain;

use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class TranslationService
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain
 *
 * @since TB 1.3
 */
class TranslationService {

	/** @var Actions */
	private $wp_actions;

	/**
	 * TranslationService constructor.
	 *
	 * @param Actions $wp_actions
	 */
	public function __construct( Actions $wp_actions ) {
		$this->wp_actions = $wp_actions;
	}

	/**
	 * Get line object as it is expected by WPML.
	 *
	 * @param string $original
	 * @param string $title
	 * @param string $element_name
	 *
	 * @param string $id
	 *
	 * @return \stdClass
	 */
	public function get_line_object( $original, $title, $element_name ) {
		return (object) [
			'id' => $this->get_object_id( $original, $element_name ),
			'name' => $title,
			'title' => $title,
			'value' => $original,
			'type' => 'LINE'
		];
	}

	/**
	 * @param string $original
	 * @param array $translations
	 * @param string $element_name
	 * @param string $lang
	 *
	 * @return string
	 */
	public function get_translated_text_by_translations( $original, $translations, $element_name, $lang ) {
		if( ! is_string( $original ) ) {
			throw new \InvalidArgumentException( '$original must be a string.' );
		}

		if( ! is_array( $translations ) ) {
			throw new \InvalidArgumentException( '$translations must be a an array.' );
		}

		if( ! is_string( $lang ) ) {
			throw new \InvalidArgumentException( '$lang must be a string.' );
		}

		if( empty( $original ) ) {
			// Nothing to translate.
			return '';
		}

		$id = $this->get_object_id( $original, $element_name );

		if(
			array_key_exists( $id, $translations )
			&& is_array( $translations[ $id ] ) && array_key_exists( $lang, $translations[ $id ] )
			&& is_array( $translations[ $id ][ $lang ] ) && array_key_exists( 'value', $translations[ $id ][ $lang ] )
		) {
			// Translation found.
			return (string) $translations[ $id ][ $lang ]['value'];
		}

		// No translation found.
		return '';
	}

	/**
	 * @param string $original
	 * @param string $element_name
	 *
	 * @return string
	 */
	private function get_object_id( $original, $element_name ) {
		return md5( $original . $element_name );
	}

	public function apply_string_to_shortcode_package( $string, \WP_Post $post ) {
		$this->wp_actions->do_action(
			'wpml_register_string',
			$string->value,
			$string->id,
			$this->get_shortcode_package( $post->ID ),
			$string->name,
			$string->type
		);
	}

	private function get_shortcode_package( $original_post_id ) {
		return [
			'kind'    => 'Advanced Shortcode Attributes',
			'name'    => $original_post_id,
			'title'   => 'Advanced Shortcode Attributes ' . $original_post_id,
			'post_id' => $original_post_id,
		];
	}
}
