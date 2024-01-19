<?php

abstract class CRED_Validator_Base {

	protected $_post_id;
	protected $_formData;
	protected $_formHelper;
	protected $_zebraForm;

	public function get_form_data() {
		return $this->_formData;
	}

	/**
	 * @return CRED_Form_Rendering
	 */
	public function get_form_rendering() {
		return $this->_zebraForm;
	}

	public function get_form_helper() {
		return $this->_formHelper;
	}

	/**
	 * CRED_Validator_Base constructor.
	 *
	 * @param CRED_Form_Base $base_form
	 */
	public function __construct( $base_form ) {
		$this->_post_id = $base_form->get_post_id();
		$this->_formData = $base_form->get_form_data();
		$this->_formHelper = $base_form->get_form_helper();
		$this->_zebraForm = $base_form->get_form_rendering();
	}

}