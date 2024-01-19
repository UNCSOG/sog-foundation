<?php

/**
 * Class that handles Toolset Forms User Form custom errors message set on CRED_Form_Rendering
 *
 * @since 1.9.3
 */
class CRED_User_Form_Custom_Validation_Error_Message_Handler extends CRED_Base_Custom_Validation_Error_Message_Handler {

	/**
	 * @param $errors   array($field_slug1 => $error_text, ..., $field_slugN => $error_text )
	 *
	 * @return bool
	 */
	public function handle_custom_validation_errors_messages( $errors ) {
		$no_error = true;

		foreach ( $errors as $field_slug => $error_text ) {
			if ( strpos( $field_slug, "wpcf-" ) !== false ) {
				$field_slug = str_replace( "wpcf-", "", $field_slug );
			}

			//If the field exists in form fields
			if (
				! isset( CRED_StaticClass::$out['form_fields'] )
				|| ! array_key_exists( $field_slug, CRED_StaticClass::$out['form_fields'] )
			) {
				continue;
			}

			//Added result to fix conditional elements of this todo
			//Notice: Undefined index: cred_form_6_1_wysiwyg-field in with validation hook
			if (
				isset( CRED_StaticClass::$out['fields']['user_fields'][ $field_slug ] )
				&& isset( CRED_StaticClass::$out['fields']['user_fields'][ $field_slug ]['plugin_type_prefix'] )
			) {
				$field_name_with_prefix = CRED_StaticClass::$out['fields']['user_fields'][ $field_slug ]['plugin_type_prefix'] . $field_slug;

				//Added exception controls on images validation, Files and special fields like checkboxes/checkbox/radio
				if (
					(
					! in_array( CRED_StaticClass::$out['fields']['user_fields'][ $field_slug ]['type'], array( 'checkboxes', 'checkbox', 'radio' ) )
					)
					&& ! isset( $_POST[ $field_name_with_prefix ] )
					&& ! isset( $_FILES[ $field_name_with_prefix ] )
				) {
					continue;
				}
			}

			//Fix of cred_form_validate_form_'.$form_slug doesn't work
			$field_name = $this->get_field_name( $field_slug );
			$this->cred_form_rendering->add_top_message( $field_name . ": " . $error_text );

			$no_error = false;
		}

		return $no_error;
	}
}