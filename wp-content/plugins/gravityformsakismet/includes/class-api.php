<?php

namespace Gravity_Forms\Gravity_Forms_Akismet;

use WP_Error;

/**
 * Interacts with the Akismet REST API.
 *
 * @since 1.1
 */
class API {

	/**
	 * The base Akismet API URL.
	 *
	 * @since 1.1
	 *
	 * @var string $api_url
	 */
	protected $api_url = 'https://rest.akismet.com/1.1/';

	/**
	 * The API key.
	 *
	 * @since 1.1
	 *
	 * @var null|GF_Akismet $add_on The current instance of the add-on.
	 */
	protected $add_on;

	/**
	 * Initializes an instance of this class.
	 *
	 * @since 1.1
	 *
	 * @param null|GF_Akismet $add_on The current instance of the add-on.
	 *
	 * @return void
	 */
	public function __construct( $add_on = null ) {
		$this->add_on = $add_on instanceof GF_Akismet ? $add_on : gf_akismet();
	}

	/**
	 * Makes the API request.
	 *
	 * @since 1.1
	 *
	 * @param string $path          Request path.
	 * @param array  $args          The query arguments or data for the request body.
	 * @param string $method        Request method. Defaults to POST.
	 * @param int    $expected_code The expected response code.
	 *
	 * @return array|WP_Error
	 */
	private function make_request( $path, $args = array() ) {
		$request_url = $this->api_url . $path;

		if ( empty( $args['api_key'] ) ) {
			$args['api_key'] = $this->add_on->get_plugin_setting( 'api_key' );
		}

		if ( empty( $args['blog'] ) ) {
			$args['blog'] = home_url();
		}

		$request_args = array(
			'method'     => 'POST',
			'headers'    => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'user-agent' => sprintf( 'WordPress/%s | Gravity Forms Akismet Add-On/%s', rgar( $GLOBALS, 'wp_version' ), $this->add_on->get_version() ),
			'body'       => http_build_query( $args ),
		);

		$response = wp_remote_request( $request_url, $request_args );

		return $this->get_simplified_response( $response );
	}

	/**
	 * If the request was successful, returns a simpler array containing just the response headers and body.
	 *
	 * @since 1.1
	 *
	 * @param array|WP_Error $response The request response.
	 *
	 * @return array|WP_Error
	 */
	private function get_simplified_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array(
			'headers' => wp_remote_retrieve_headers( $response ),
			'body'    => wp_remote_retrieve_body( $response ),
		);
	}

	/**
	 * Contacts Akismet to verify the given API key is valid.
	 *
	 * @since 1.1
	 *
	 * @param string $api_key The API key.
	 *
	 * @return array|WP_Error
	 */
	public function verify_key( $api_key ) {
		return $this->make_request( 'verify-key', array( 'api_key' => $api_key ) );
	}

	/**
	 * Sends the given data to Akismet for analysis.
	 *
	 * @since 1.1
	 *
	 * @param array $data The data to be sent to Akismet.
	 *
	 * @return array|WP_Error
	 */
	public function spam_check( $data ) {
		return $this->make_request( 'comment-check', $data );
	}

	/**
	 * Sends the given data to Akismet, reporting it as spam.
	 *
	 * @since 1.1
	 *
	 * @param array $data The data to be sent to Akismet.
	 *
	 * @return array|WP_Error
	 */
	public function submit_spam( $data ) {
		return $this->make_request( 'submit-spam', $data );
	}

	/**
	 * Sends the given data to Akismet, reporting it as ham (not spam).
	 *
	 * @since 1.1
	 *
	 * @param array $data The data to be sent to Akismet.
	 *
	 * @return array|WP_Error
	 */
	public function submit_ham( $data ) {
		return $this->make_request( 'submit-ham', $data );
	}

}
