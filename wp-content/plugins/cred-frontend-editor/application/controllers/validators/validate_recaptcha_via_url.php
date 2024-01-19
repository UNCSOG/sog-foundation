<?php

/**
 * CRED_Validate_Recaptcha Support Class that validate via url by form_id and set its local cache
 *
 * @since 1.9.1
 */
class CRED_Validate_Recaptcha_Via_Url {

	protected static $recaptcha_validation_forms_result_cache;

	/**
	 * @param int $form_id
	 * @param string $validation_url
	 *
	 * @return mixed
	 */
	public function validate( $form_id, $validation_url ) {
		if ( ! isset( self::$recaptcha_validation_forms_result_cache[ $form_id ] ) ) {
			//Try to use curl_init
			if ( function_exists( 'curl_init' ) ) {
				// Get cURL resource
				$curl = curl_init();

				// Set some options
				curl_setopt_array( $curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => $validation_url,
				) );

				// Send the request
				$response = curl_exec( $curl );
				// Close request to clear up some resources
				curl_close( $curl );
			}

			//Try file_get_contents
			if ( ! isset( $response ) || empty( $response ) ) {
				$response = file_get_contents( $validation_url );
			}
			$response = json_decode( $response, true );

			self::$recaptcha_validation_forms_result_cache[ $form_id ] = $response["success"];
		}

		return self::$recaptcha_validation_forms_result_cache[ $form_id ];
	}
}