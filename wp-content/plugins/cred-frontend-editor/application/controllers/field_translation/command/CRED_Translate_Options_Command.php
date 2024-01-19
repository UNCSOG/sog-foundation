<?php

abstract class CRED_Translate_Options_Command extends CRED_Translate_Field_Command_Base {

	/**
	 * @param $option
	 * @param $key
	 * @param $form
	 * @param $field
	 *
	 * @return mixed
	 */
	protected function _cred_translate_option( $option, $key, $form, $field ) {
		if ( ! isset( $option['title'] ) ) {
			return $option;
		}
		$original = $option['title'];
		$option['title'] = cred_translate(
			$field['slug'] . " " . $option['title'], $option['title'], $this->cred_form_prefix
		);
		if ( $original == $option['title'] ) {
			// Try translating with types context
			$option['title'] = cred_translate(
				'field ' . $field['id'] . ' option ' . $key . ' title', $option['title'], 'plugin Types' );
		}

		return $option;
	}

}