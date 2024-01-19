<?php

/**
 * Class CRED_Field_Translation_Result
 * used as result object of CRED_Translate_Field_Command_Interface::execute() methods
 *
 * @since 1.9.1
 */
class CRED_Field_Translation_Result {

	private $field_configuration;
	private $field_type;
	private $field_name;
	private $field_value;
	private $field_attributes;
	private $field;

	/**
	 * CRED_Field_Translation_Result constructor.
	 *
	 * @param string|array $field_configuration
	 * @param string $field_type
	 * @param string $field_name
	 * @param string $field_value
	 * @param array $field_attributes
	 * @param object $field
	 */
	public function __construct(
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
	}

	/**
	 * return the current field value element final and translated
	 * from CRED_Field_Configuration_Translated_Value
	 *
	 * @return string|array
	 */
	public function get_field_configuration() {
		return $this->field_configuration;
	}

	/**
	 * return the field type (textfield, data, ... )
	 *
	 * @return string
	 */
	public function get_field_type() {
		return $this->field_type;
	}

	/**
	 * @return string
	 */
	public function get_field_name() {
		return $this->field_name;
	}

	/**
	 * return the field original value that could be different by field_configuration
	 *
	 * @return string
	 */
	public function get_field_value() {
		return $this->field_value;
	}

	/**
	 * return the set of attributes related to the current field like class, style and
	 * also custom tag attributes
	 *
	 * @return array
	 */
	public function get_field_attributes() {
		return $this->field_attributes;
	}

	/**
	 * return the full field object
	 *
	 * @return object
	 */
	public function get_field() {
		return $this->field;
	}


}