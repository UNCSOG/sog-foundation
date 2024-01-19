<?php

class CRED_Translate_Form_submit_Command extends CRED_Translate_Field_Command_Base {

	function __construct( CRED_Translate_Field_Factory $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field ) {
		parent::__construct( $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field );

		$this->field_type = 'submit';
	}

	public function execute() {
		$preset_value = isset($this->additional_args['preset_value']) ? $this->additional_args['preset_value'] : "";
		$placeholder = isset($this->additional_args['placeholder']) ? $this->additional_args['placeholder'] : "";

		static $_count_ = array(
			'submit' => 0,
		);

		if ( isset( $preset_value )
			&& ! empty( $preset_value )
			&& is_string( $preset_value )
		) {
			// use translated value by WPML if exists
			$this->field_configuration = cred_translate(
				'Value: ' . $preset_value, $preset_value, $this->cred_form_prefix
			);
			$this->field_value = $this->field_configuration;
			$additional_options['preset_value'] = $placeholder;
		}

		// allow multiple submit buttons
		$this->field_name .= '_' . ++$_count_['submit'];

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}
}