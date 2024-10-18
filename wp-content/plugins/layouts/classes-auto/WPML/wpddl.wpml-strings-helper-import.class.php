<?php

/**
 * Class WPDD_WPML_Strings_Helper_Import
 * Given a Layout valid JSON string with a valid translations array field, finds all the cells with registered [wpml-string] occurences
 * in the given layout and by comparing their occurences in the translation array, register them as WPML string packages in the new site during an import process
 */
class WPDD_WPML_Strings_Helper_Import extends WPDD_WPML_Strings_Helper implements WPDD_WPML_Strings_Helper_Interface{
	/**
	 * @param null $translations
	 */
	public function wpml_set_translated_strings( $translations = null ){

		if( !$translations || count( $translations ) === 0 ) return;

		$this->translations = $translations;

		$contexts = $this->populate_strings_array( );

		if( ! $contexts || count( $contexts ) === 0 ) return;

		foreach( $contexts as $key => $context ){
			if( isset( $this->translations[$key] ) ){
				do_action('wpml_set_translated_strings',
					array( $key => $this->translations[$key] ),
					array(
						'kind' => 'Layout',
						'name' => $context
					)
				);
			}
		}
	}

	/**
	 * @return array|mixed
	 */
	public function wpml_get_translated_strings( ){
		return $this->translations;
	}

	/**
	 * @param $cell
	 * @param $content
	 *
	 * @return array
	 */
	protected function process_cell_strings( $cell, $content ){

		if( !$this->translations || count( $this->translations ) === 0 ) return array();

		$translations = $this->translations;

		$wpml_string_finder = $this->get_string_parser();

		$strings = $wpml_string_finder->find( $content );

		$contexts = array();

		foreach( $strings as $string ){

			$key = $this->get_string_name_for_wpml_string( $cell->get_unique_id(), $string['string'] );

			if( isset( $translations[$key] ) ){
				if( isset( $string['context'] ) ){
					$contexts[$key] = $string['context'];
				} else {
					$contexts[$key] = $key;
				}
			}
		}

		return $contexts;
	}
}