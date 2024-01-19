<?php

class CRED_Translate_Radio_Command extends CRED_Translate_Options_Command {

	function __construct( CRED_Translate_Field_Factory $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field ) {
		parent::__construct( $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field );

		$this->field_type = 'radios';
	}

	public function execute() {
		$titles = array();

		// Store the currently set field attributes because the property gets overwritten during the process, but
		// we will need it later. This is a hack to prevent potentially dangerous changes in this legacy codebase.
		//
		// Used to preserve the "output" attribute.
		$field_attributes_backup = $this->field_attributes;

		$default = isset( $this->field['data']['options']['default'] ) ? $this->field['data']['options']['default'] : "";
		if ( isset( $this->field['data']['options']['default'] ) ) {
			unset( $this->field['data']['options']['default'] );
		}

		$set_default = false;
		if ( isset( $this->field['data']['options'] ) && ! empty( $this->field['data']['options'] ) ) {
			foreach ( $this->field['data']['options'] as $key => &$option ) {
				if ( isset( $option['value'] ) ) {
					$option['value'] = str_replace( "\\", "", $option['value'] );
				}

				if ( ! $set_default
					&& $key === $default
				) {
					$set_default = true;
					$default = $option['value'];
				}

				$index = $key;

				if ( is_admin() ) {
					//register strings on form save
					cred_translate_register_string( $this->cred_form_prefix, $this->field['slug'] . " " . $option['title'], $option['title'], false );
				}
				$option = $this->_cred_translate_option( $option, $key, $this->form, $this->field );

				$titles[ $index ] = $option['title'];

				if ( isset( $this->field_configuration )
					&& $this->field_configuration == $option['value']
				) {
					$this->field_attributes = isset( $option['value'] ) ? $option['value'] : $key;
					$this->field_value = isset( $option['value'] ) ? $option['value'] : $key;
				}
			}
		}

		if ( 
			(
				! isset( $this->field_configuration )
				|| (
					empty( $this->field_configuration ) 
					&& ! is_numeric( $this->field_configuration ) 
				)
			) && ( 
				! empty( $default ) 
				|| is_numeric( $default ) 
			) 
		) {
			// Use the Types stored field default when the externally forced field value is empty.
			// This happens because the cred_field has a default empty value attribute that gets here as field_configuration
			// which might override here otherwise
			$this->field_attributes = $default;
		}
		$default_attributes = $this->field_attributes;
		$this->field_attributes = array( 'default' => $default_attributes );
		$this->field_attributes['actual_titles'] = $titles;

		if ( isset( CRED_StaticClass::$out['field_values_map'][ $this->field['slug'] ] ) ) {
			$this->field_attributes['actual_values'] = CRED_StaticClass::$out['field_values_map'][ $this->field['slug'] ];
		}

		foreach ( $this->field_attributes['actual_values'] as $k => &$option ) {
			$option = str_replace( "\\", "", $option );
		}

		if( array_key_exists( 'output', $field_attributes_backup ) ) {
			$this->field_attributes['output'] = $field_attributes_backup['output'];
		}

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}
}
