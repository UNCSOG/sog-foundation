<?php

/**
 * Class CRED_Translate_Command_Factory composed by the function get_command_class_instance that return the right
 * Command Class Object by Type field
 *
 * @since 1.9.1
 */
class CRED_Translate_Command_Factory {
	
	const FALLBACK_COMMAND = "CRED_Translate_Textfield_Command";

	/**
	 * @param CRED_Translate_Field_Factory $cred_translate_field_factory
	 * @param object $field
	 * @param array $attributes
	 *
	 * @return CRED_Field_Translation_Result
	 */
	public function get_command_class_instance(
		CRED_Translate_Field_Factory $cred_translate_field_factory,
		$field,
		$attributes
	) {
		$class_type = ucfirst( $field['type'] );
		$command_class = "CRED_Translate_{$class_type}_Command";
		
		if ( ! class_exists( $command_class ) ) {
			$command_class = self::FALLBACK_COMMAND;
		}

		$command_field = new $command_class( $cred_translate_field_factory, $field['field_configuration'], $field['type'], $field['field_name'], $field['field_value'], $attributes, $field );
		
		return $command_field;
	}


}