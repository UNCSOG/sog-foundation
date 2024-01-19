<?php

class CRED_Translate_Recaptcha_Command extends CRED_Translate_Field_Command_Base {

	public function execute() {
		$globals = CRED_StaticClass::$_staticGlobal;

		$this->field_value = '';
		$this->field_attributes = array(
			'error_message' => $this->cred_translate_field_factory->_formHelper->getLocalisedMessage( 'enter_valid_captcha' ),
			'show_link' => $this->cred_translate_field_factory->_formHelper->getLocalisedMessage( 'show_captcha' ),
			'no_keys' => __( 'Enter your ReCaptcha keys in the Forms tab of the Toolset Settings page in order for ReCaptcha API to work', 'wp-cred' ),
		);
		if ( false !== $globals['RECAPTCHA'] ) {
			$this->field_attributes['public_key'] = $globals['RECAPTCHA']['public_key'];
			$this->field_attributes['private_key'] = $globals['RECAPTCHA']['private_key'];
		}

		// used to load additional js script
		CRED_StaticClass::$out['has_recaptcha'] = true;

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}
}
