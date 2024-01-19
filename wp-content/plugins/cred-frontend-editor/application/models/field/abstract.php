<?php

abstract class CRED_Field_Abstract {

	protected $_atts;
	protected $cred_form_rendering;
	protected $_formHelper;
	protected $_formData;
	protected $_translate_field_factory;

	/**
	 * CRED_Field_Abstract constructor.
	 *
	 * @param array $atts
	 * @param CRED_Form_Rendering $cred_form_rendering
	 * @param CRED_Form_Builder_Helper $formHelper
	 * @param CRED_Form_Data $formData
	 * @param CRED_Translate_Field_Factory $translate_field_factory
	 */
	public function __construct( $atts, $cred_form_rendering, CRED_Form_Builder_Helper $formHelper, $formData, CRED_Translate_Field_Factory $translate_field_factory ) {
		$this->_atts = $atts;
		$this->cred_form_rendering = $cred_form_rendering;
		$this->_formHelper = $formHelper;
		$this->_formData = $formData;
		$this->_translate_field_factory = $translate_field_factory;
	}

	/**
	 * Get the field structure array after a validation, translation and creation flow
	 *
	 * @return mixed
	 */
	abstract protected function get_field();

}
