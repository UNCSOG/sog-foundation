<?php

/**
 * Class CRED_Translate_Field_Command_Base
 * Super Class Command that handle all the fields translation from CRED_Translate_Field_Factory
 *
 * @since 1.9.1
 */
class CRED_Translate_Field_Command_Base implements CRED_Translate_Field_Command_Interface {

	protected $additional_args;
	protected $field_configuration;
	protected $field_type;
	protected $field_name;
	protected $field_value;
	protected $field_attributes;
	protected $field;
	protected $form;
	protected $cred_form_prefix;
	protected $cred_translate_field_factory;

	/**
	 * CRED_Translate_Field_Command_Base constructor.
	 *
	 * @param CRED_Translate_Field_Factory $cred_translate_field_factory
	 * @param $field_configuration
	 * @param $field_type
	 * @param $field_name
	 * @param string $field_value
	 * @param $field_attributes
	 * @param $field
	 */
	function __construct(
		CRED_Translate_Field_Factory $cred_translate_field_factory,
		$field_configuration,
		$field_type,
		$field_name,
		$field_value,
		$field_attributes,
		$field
	) {
		$this->field_configuration = $field_configuration;
		$this->field_type = $field_type;
		$this->field_name = $field_name;
		$this->field_value = $field_value;
		$this->field_attributes = $field_attributes;
		$this->field = $field;
		$this->cred_translate_field_factory = $cred_translate_field_factory;

		$this->form = $form = $this->cred_translate_field_factory->_formBuilder->get_form_data();
		$this->cred_form_prefix = 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID;
	}

	/**
	 * @return CRED_Field_Translation_Result
	 */
	public function execute() {
		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function set_additional_args( $name, $value ) {
		$this->additional_args[ $name ] = $value;
	}
}
