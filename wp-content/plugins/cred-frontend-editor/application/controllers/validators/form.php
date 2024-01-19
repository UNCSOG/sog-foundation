<?php

class CRED_Validator_Form extends CRED_Validator_Base {

    protected $_base_form;
    protected $_legacy_errors;

    public function __construct($base_form, $legacy_errors = "") {
        parent::__construct($base_form);

        $this->_base_form = $base_form;
        $this->_legacy_errors = $legacy_errors;
    }

    public function validate() {
        $result = array();

        $form = $this->_formData;

		$form_action_factory = new \OTGS\Toolset\CRED\Controller\FormAction\Factory();
		$message_controller = $form_action_factory->get_message_controller_by_form_type( $form->getForm()->post_type );

	    $is_user_form = ( $form->getForm()->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME );
        $cred_form_rendering = $this->_zebraForm;

	    if ( ! $cred_form_rendering->is_submitted ) {
		    return false;
	    }

        $legacy_validator = new CRED_Validator_Legacy($this->_legacy_errors);
        $result[] = $legacy_validator->validate();

        $post_validator = new CRED_Validator_Post($this->_base_form, $message_controller );
        $result[] = $post_validator->validate();

        $nonce_validator = new CRED_Validator_Nonce($this->_base_form, $message_controller );
        $result[] = $nonce_validator->validate();

        $recaptcha_validator = new CRED_Validator_Recaptcha($this->_base_form, new CRED_Validate_Recaptcha_Via_Url(), $message_controller );
        $result[] = $recaptcha_validator->validate();

        $user_validator = new CRED_Validator_User($this->_base_form, null, $message_controller );
        $result[] = $user_validator->validate();

        $fields_validator = new CRED_Validator_Fields($this->_base_form);
        $result[] = $fields_validator->validate();

        $toolset_validator = new CRED_Validator_Toolset_Forms($cred_form_rendering, $this->_post_id, $is_user_form);
        $result[] = $toolset_validator->validate();

        return (count(array_unique($result)) === 1);
    }

}
