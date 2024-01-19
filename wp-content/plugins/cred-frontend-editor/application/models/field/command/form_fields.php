<?php

/**
 * Class that transform "Form Fields" shortcode attributes to field object for rendering fields
 *
 * - form
 * - form_submit
 * - form_messages
 *
 * @since 1.9.6
 */
class CRED_Field_Command_Form_Fields extends CRED_Field_Command_Base {

	public function execute() {
		$field = CRED_StaticClass::$out[ 'fields' ][ 'form_fields' ][ $this->field_name ];

		$name = $this->field_name;
		$field[ 'form_html_id' ] = $this->translate_field_factory->get_html_form_field_id( $field );

		$additional_attributes = array(
			'preset_value' => $this->value,
			'urlparam' => $this->filtered_attributes['urlparam'],
			'make_readonly' => $this->readonly,
			'max_width' => $this->filtered_attributes['max_width'],
			'max_height' => $this->filtered_attributes['max_height'],
			'class' => $this->filtered_attributes['class'],
			'output' => $this->filtered_attributes['output'],
			'placeholder' => $this->filtered_attributes['placeholder'],
		);

		//Do not delete this commented code, it is a new feature we will enable on the next future release
		//CRED_Select2_Utils::get_instance()->try_register_field_as_select2( $this->cred_form_rendering->html_form_id, $name, $field, $use_select2 );

		$field_object = $this->translate_field_factory->cred_translate_field( $name, $field, $additional_attributes );

		if ( $this->form_type == 'edit'
			&& ( $field_object[ 'name' ] == 'user_pass' ||
				$field_object[ 'name' ] == 'user_pass2' )
		) {
			if ( isset( $field_object[ 'data' ][ 'validate' ] )
				&& isset( $field_object[ 'data' ][ 'validate' ][ 'required' ] )
			) {
				unset( $field_object[ 'data' ][ 'validate' ][ 'required' ] );
			}
		}

		// check which fields are actually used in form
		CRED_StaticClass::$out[ 'form_fields' ][ $name ] = $this->get_uniformed_field( $field, $field_object );
		CRED_StaticClass::$out[ 'current_form_fields' ][ $name ] = $this->get_uniformed_field( $field, $field_object );
		CRED_StaticClass::$out[ 'form_fields_info' ][ $name ] = array(
			'type' => $field[ 'type' ],
			'repetitive' => ( isset( $field[ 'data' ][ 'repetitive' ] ) && $field[ 'data' ][ 'repetitive' ] ),
			'plugin_type' => ( isset( $field[ 'plugin_type' ] ) ) ? $field[ 'plugin_type' ] : '',
			'name' => $name,
		);

		return $field_object;
	}
}