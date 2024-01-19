<?php
/**
 * WPML integration for translating forms, using legacy WPML ST.
 *
 * @package Toolset Forms
 * @since 2.6
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\Origin;

/**
 * Forms translation controller using legacy WPML ST.
 *
 * @since 2.6
 */
class TypesFieldTranslator {
	const TYPES_DOMAIN = 'plugin Types';

	/**
	 * Types field data
	 *
	 * @var Toolset_Field_Definition
	 */
	private $field_definition;

	/**
	 * Set Field data
	 *
	 * @param array $data Field data
	 */
	public function set_field_definition( \Toolset_Field_Definition $field_definition ) {
		$this->field_definition = $field_definition;
	}

	/**
	 * Gets the translation for all active languages of a field attribute using WPML ST
	 *
	 * @param $value string Text to be translated
	 * @param $name string Name of the string
	 * @return string
	 */
	private function get_wpml_st_translations( $value, $name ) {
		$translations = [];
		$languages = $languages = apply_filters( 'wpml_active_languages', NULL );
		$current_language = apply_filters( 'wpml_current_language', NULL );
		foreach( array_keys( $languages ) as $language ) {
			if ( $language !== $current_language ) {
				$translation = apply_filters( 'wpml_translate_single_string', $value, self::TYPES_DOMAIN, $name, $language );
				if ( ! empty( $translation ) ) {
					$translations[ $language ] = [
						'value' => $translation,
						'status' => ICL_TM_IN_PROGRESS,
					];
				}
			}
		}
		return $translations;
	}

	/**
	 * Gets the translations for all active languages of a field attribute using WPML ST or TM packages
	 *
	 * @param $text string Text to be translated
	 * @param $key string Name of the string
	 * @return string
	 */
	public function get_translations( $text, $key ) {
		if ( ! $this->field_definition ) {
			return $text;
		}
		if ( empty( $text ) ) {
			return null;
		}
		$string_name = '';
		switch( $key ) {
			case 'name':
			case 'placeholder':
			case 'default value':
			case 'value not selected':
			case 'value selected':
				$string_name = sprintf( 'field %s %s', $this->field_definition->get_slug(), $key );
				break;
		}
		return $this->get_wpml_st_translations( $text, $string_name );
	}
}
