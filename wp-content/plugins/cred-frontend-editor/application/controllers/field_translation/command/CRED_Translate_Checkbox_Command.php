<?php

class CRED_Translate_Checkbox_Command extends CRED_Translate_Field_Command_Base {

	public function execute() {
		$save_empty = isset( $this->field['data']['save_empty'] ) ? $this->field['data']['save_empty'] : false;
		//If save empty and $_POST is set but checkbox is not set data value 0

		if ( isset( $this->field_configuration )
			&& $this->field_configuration == 1
			&& $save_empty == 'no'
			&& isset( $_POST )
			&& ! empty( $_POST )
			&& ! isset( $_POST[ $this->field_name ] )
		) {
			$this->field_configuration = 0;
		}

		$this->field_value = isset( $this->field['data']['set_value'] ) ? $this->field['data']['set_value'] : "";

		if ( isset( $this->field_configuration ) && $this->field_configuration == $this->field_value ) {
			$this->field_attributes['checked'] = 'checked';
		}

		if ( is_admin() ) {
			//register strings on form save
			cred_translate_register_string( $this->cred_form_prefix, $this->field['slug'], $this->field['name'], false );
		}

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}
}