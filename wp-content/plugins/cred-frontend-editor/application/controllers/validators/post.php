<?php

class CRED_Validator_Post extends CRED_Validator_Base implements ICRED_Validator {
	/**
	 * Message controoler
	 *
	 * @var \OTGS\Toolset\CRED\Controller\FormAction\Message\Base
	 */
	private $message_controller;

	/**
	 * CRED_Validator_Base constructor.
	 *
	 * @param CRED_Form_Base $base_form
	 *
	 */
	public function __construct( $base_form, \OTGS\Toolset\CRED\Controller\FormAction\Message\Base $message_controller ) {
		parent::__construct( $base_form );
		$this->message_controller = $message_controller;
	}

	public function validate() {
		$result = true;
		if (empty($_POST)) {
			// This happens when the form is submitted but no data was posted
			// We are trying to upload a file greater then the maximum allowed size
			// So we should display a custom error
			//$zebraForm->add_form_error('security', $formHelper->getLocalisedMessage('no_data_submitted'));
			$message = $this->message_controller->get_message_by_id( $this->_formHelper->get_form_data(), 'no_data_submitted' );
			$this->_zebraForm->add_top_message( $message );
			$this->_zebraForm->add_field_message( $message );
			$result = false;
		}
		return $result;
	}

}
