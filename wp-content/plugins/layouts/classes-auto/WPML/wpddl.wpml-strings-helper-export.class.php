<?php

/**
 * Class WPDD_WPML_Strings_Helper_Export
 * Given a Layout valid JSON string finds all the cells with registered [wpml-string] occurences and prepares them in an array for export
 */
class WPDD_WPML_Strings_Helper_Export extends WPDD_WPML_Strings_Helper implements WPDD_WPML_Strings_Helper_Interface{
	/**
	 * @return array|null
	 */
	public function wpml_get_translated_strings( ){

		$strings = $this->populate_strings_array();

		if( ! $strings || count( $strings ) === 0 ) return null;

		foreach( $strings as $string ){

			$translation = apply_filters('wpml_get_translated_strings',
				array(),
				array(
					'kind' => 'Layout',
					'name' => $string
				)
			);

			if( $translation ){
				$this->wpml_set_translated_strings( array_merge( $this->translations, $translation ) );
			}
		}

		return $this->translations;
	}

	/**
	 * @param null $translations
	 *
	 * @return mixed|void
	 */
	public function wpml_set_translated_strings( $translations = null ){
		$this->translations = $translations;
	}

	/**
	 * @param $cell
	 * @param $content
	 *
	 * @return array
	 */
	protected function process_cell_strings( $cell, $content ){

		$wpml_string_finder = $this->get_string_parser();

		$strings = $wpml_string_finder->find( $content );

		$contexts = array();

		foreach( $strings as $string ){
			if( isset( $string['context'] ) ){
				$contexts[] = $string['context'];
			} else {
				$contexts[] = $this->get_string_name_for_wpml_string( $cell->get_unique_id(), $string['string'] );
			}
		}

		return $contexts;
	}
}