<?php
define( 'CRED_GOOGLE_RECAPTCHA_V2_VALIDATION_URL', 'https://www.google.com/recaptcha/api/siteverify' );

/**
 * Class responsible of the Recaptcha Field if exists
 *
 * @since unknown
 */
class CRED_Validator_Recaptcha extends CRED_Validator_Base implements ICRED_Validator {

	protected $recaptcha_via_url;

	function __construct( CRED_Form_Base $base_form, CRED_Validate_Recaptcha_Via_Url $recaptcha_via_url, \OTGS\Toolset\CRED\Controller\FormAction\Message\Base $message_controller ) {
		parent::__construct( $base_form );

		$this->recaptcha_via_url = $recaptcha_via_url;
		$this->message_controller = $message_controller;
	}

	/**
	 * @return bool
	 */
	public function validate() {
		$form_id = $this->_formData->getForm()->ID;
		$zebraForm = $this->_zebraForm;
		$formHelper = $this->_formHelper;

		$result = true;
		if ( isset( $_POST['_recaptcha'] ) ) {
			if (
			( isset( $_POST["g-recaptcha-response"] )
				&& ! empty( $_POST["g-recaptcha-response"] ) )
			) {
				$captcha = $_POST['g-recaptcha-response'];

				$settings_model = CRED_Loader::get( 'MODEL/Settings' );
				$settings = $settings_model->getSettings();

				$publickey = $settings['recaptcha']['public_key'];
				$privatekey = $settings['recaptcha']['private_key'];

				$secretKey = $settings['recaptcha']['private_key'];
				$ip = $_SERVER['REMOTE_ADDR'];

				if ( empty( $privatekey ) || empty( $publickey ) ) {
					$message = $this->message_controller->get_message_by_id( $this->_formHelper->get_form_data(), 'no_recaptcha_keys' );

					$zebraForm->add_top_message( $message );
					$zebraForm->add_field_message( $message );
					$result = false;
				} else {

					$params = array();
					$params['secret'] = $secretKey; // Secret key
					if ( ! empty( $_POST ) && isset( $_POST['g-recaptcha-response'] ) ) {
						$params['response'] = urlencode( $_POST['g-recaptcha-response'] );
					}
					$params['remoteip'] = $_SERVER['REMOTE_ADDR'];

					$params_string = http_build_query( $params );
					$requestURL = CRED_GOOGLE_RECAPTCHA_V2_VALIDATION_URL . "?" . $params_string;

					$is_recaptcha_validation_result = $this->recaptcha_via_url->validate( $form_id, $requestURL );

					if ( ! $is_recaptcha_validation_result ) {
						//$zebraForm->add_form_error('security', $formHelper->getLocalisedMessage('enter_valid_captcha'));
						$message = $this->message_controller->get_message_by_id( $this->_formHelper->get_form_data(), 'enter_valid_captcha' );
						$zebraForm->add_top_message( $message );
						$zebraForm->add_field_message( $message );
						$result = false;
					}
				}
			} else {
				$message = $this->message_controller->get_message_by_id( $this->_formHelper->get_form_data(), 'missing_captcha' );
				$zebraForm->add_top_message( $message );
				$zebraForm->add_field_message( $message );
				$result = false;
			}
		}

		return $result;
	}

}
