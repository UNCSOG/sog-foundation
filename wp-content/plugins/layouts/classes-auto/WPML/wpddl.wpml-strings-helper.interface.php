<?php

/**
 * Interface WPDD_WPML_Strings_Helper
 * Provides a base interface to interact with [wpml-string] created String Packages API
 */
interface WPDD_WPML_Strings_Helper_Interface{
	/**
	 * @return mixed
	 */
	public function wpml_get_translated_strings();

	/**
	 * @param null $translations
	 *
	 * @return mixed
	 */
	public function wpml_set_translated_strings( $translations = null );
}