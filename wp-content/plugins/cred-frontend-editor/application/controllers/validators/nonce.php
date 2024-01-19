<?php

class CRED_Validator_Nonce extends CRED_Validator_Base implements ICRED_Validator {
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
		if (is_user_logged_in()) {
			$nonce_id = substr($this->_zebraForm->form_properties['name'], 0, strrpos($this->_zebraForm->form_properties['name'], '_'));
			if (!array_key_exists(CRED_StaticClass::NONCE . "_" . $nonce_id, $_POST) ||
					!wp_verify_nonce($_POST[CRED_StaticClass::NONCE . "_" . $nonce_id], $nonce_id)) {
				$message = $this->message_controller->get_message_by_id( $this->_formHelper->get_form_data(), 'invalid_form_submission' );
				$this->_zebraForm->add_top_message( $message );
				$this->_zebraForm->add_field_message( $message );
				$result = false;
			}
		}
		return $result;
	}

}
