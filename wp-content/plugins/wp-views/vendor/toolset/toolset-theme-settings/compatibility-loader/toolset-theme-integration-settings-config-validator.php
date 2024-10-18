<?php

class Toolset_Theme_Integration_Settings_Config_Validator {

	/**
	 * @param $json_string
	 *
	 * @return array|mixed|null|object
	 */
	private function json_decode( $json_string ){
		if ( ! $json_string ) {
			return null;
		}
		return json_decode( $json_string );
	}

	/**
	 * @since 2.5
	 *
	 * @param $json_string
	 * validates the config-toolset.json file structure.
	 *
	 * @return bool|object
	 */
	public function validate_config_file_structure( $json_string ) {
		$content = $this->json_decode( $json_string );
		if ( ! is_object( $content ) ) {
			return false;
		}

		if ( ! property_exists( $content, 'data' ) ) {
			return false;
		}

		if ( ! is_array( $content->data ) || count( $content->data ) === 0 ) {
			return false;
		}

		/**
		 * mandatory properties
		 */
		foreach ( $content->data as $settings ) {
			if ( ! property_exists( $settings, 'type' ) ||
				! is_array( $settings->type ) ||
				! property_exists( $settings, 'name' ) ||
				! property_exists( $settings, 'gui' ) ||
				! property_exists( $settings, 'group' ) ||
				! property_exists( $settings, 'target' ) ||
				! is_array( $settings->target )
			) {
				return false;
			}
		}

		return $this->replace_info_placeholders( $content );
	}

	/**
	 * Replace placeholders in 'toolset_info' settings.
	 *
	 * Right now, we support placeholders to:
	 * - The Customizer page, as %%CUSTOMIZER%%.
	 * - The generic admin URL, as %%ADMIN%%.
	 *
	 * @param object $content
	 * @return object
	 */
	private function replace_info_placeholders( $content ) {
		foreach ( $content->data as &$settings ) {
			if (
				property_exists( $settings, 'type' )
				&& in_array( \Toolset_Theme_Integration_Settings_Model_toolset_info::TYPE, $settings->type, true )
				&& property_exists( $settings, 'gui' )
				&& property_exists( $settings->gui, 'text' )
				&& false !== strpos( $settings->gui->text, '%%' )
			) {
				$settings->gui->text = str_replace( '%%CUSTOMIZER%%', admin_url( '/customize.php' ), $settings->gui->text );
				$settings->gui->text = str_replace( '%%ADMIN%%', admin_url(), $settings->gui->text );
			}
		}

		return $content;
	}

}
