<?php

defined( 'ABSPATH' ) or die();

class GF_SendGrid_API {

	/**
	 * SendGrid API key.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $api_key SendGrid API key.
	 */
	protected $api_key;

	/**
	 * SendGrid API URL.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $api_url SendGrid API URL.
	 */
	protected $api_url = 'https://api.sendgrid.com/v3/';

	/**
	 * Scopes available for SendGrid API key.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $scopes Scopes available for SendGrid API key.
	 */
	protected $scopes = array();

	/**
	 * Initialize SendGrid API library.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $api_key SendGrid API key.
	 */
	public function __construct( $api_key ) {

		$this->api_key = $api_key;

	}

	/**
	 * Get general account statistics.
	 *
	 * @access public
	 * @param int $days (default: 30)
	 * @return array
	 */
	public function get_stats( $days = 30 ) {

		return $this->make_request( 'stats', array( 'start_date' => date( 'Y-m-d', strtotime( "- $days days" ) ) ) );

	}

	/**
	 * Check if SendGrid scope is available.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $scope Scope to check for.
	 *
	 * @return bool
	 */
	public function has_scope( $scope = '' ) {

		return in_array( $scope, $this->scopes );

	}

	/**
	 * Load SendGrid scopes to API instance.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * GFAddOn::log_error()
	 * GF_SendGrid_API::make_request()
	 *
	 * @return array
	 */
	public function load_scopes() {

		try {

			// Get scopes.
			$this->scopes = $this->make_request( 'scopes', array(), 'GET', 'scopes' );

		} catch ( Exception $e ) {

			// Log error.
			gf_sendgrid()->log_error( __METHOD__ . '(): Unable to get SendGrid scopes; ' . $e->getMessage() );

		}

		return $this->scopes;

	}

	/**
	 * Send an email.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $message Message to be sent.
	 *
	 * GFAddOn::log_debug()
	 * GF_SendGrid_API::make_request()
	 *
	 * @return array
	 */
	public function send_email( $message ) {

		return $this->make_request( 'mail/send', $message, 'POST' );

	}

	/**
	 * Make API request.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $action        Request action.
	 * @param array  $options       Request options.
	 * @param string $method        HTTP method. Defaults to GET.
	 * @param string $return_key    Array key from response to return. Defaults to null (return full response).
	 *
	 * @return array|string
	 */
	private function make_request( $action, $options = array(), $method = 'GET', $return_key = null ) {

		// Build request options string.
		$request_options = 'GET' === $method ? '?'. http_build_query( $options ) : null;

		// Build request URL.
		$request_url = $this->api_url . $action . $request_options;

		// Build request arguments.
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
		);

		// Add options to non-GET requests.
		if ( 'GET' !== $method ) {
			$args['body'] = json_encode( $options );
		}

		// Execute API request.
		$response = wp_remote_request( $request_url, $args );

		// If API request returns a WordPress error, throw an exception.
		if ( is_wp_error( $response ) ) {
			throw new Exception( 'Request failed. '. $response->get_error_message() );
		}

		// Convert JSON response to array.
		$response = json_decode( $response['body'], true );

		// If there is an error in the result, throw an exception.
		if ( isset( $response['error'] ) ) {
			throw new Exception( $response['error']['message'] );
		}

		// If there are multiple errors, convert to string and throw an exception.
		if ( isset( $response['errors'] ) ) {

			// Prepare error message.
			if ( is_array( $response['errors'] ) ) {

				// Initialize error string.
				$error = '';

				// Loop through errors.
				foreach ( $response['errors'] as $response_error ) {
					$error .= implode( ';', $response_error );
				}

			} else {

				$error = $response['errors'];

			}

			throw new Exception( $error );

		}

		// If a return key is defined and array item exists, return it.
		if ( ! empty( $return_key ) && isset( $response[ $return_key ] ) ) {
			return $response[ $return_key ];
		}

		return $response;

	}

}
