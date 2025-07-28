<?php
/**
 * API wrapper for the Recaptcha service.
 *
 * @since 1.0
 * @package Gravity_Forms\Gravity_Forms_RECAPTCHA
 */

namespace Gravity_Forms\Gravity_Forms_RECAPTCHA;

use WP_Error;

/**
 * Class RECAPTCHA_API
 *
 * @package Gravity_Forms\Gravity_Forms_RECAPTCHA
 */
class RECAPTCHA_API {
	/**
	 * Google Recaptcha token verification URL.
	 *
	 * @since 1.0
	 * @var string
	 */
	private $verification_url = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 * OAuth Access Token.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	protected $access_token;

	/**
	 * OAuth Refresh Token.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	protected $refresh_token;

	/**
	 * The GF_RECAPTCHA instance.
	 *
	 * @since 1.7.0
	 *
	 * @var GF_RECAPTCHA|null The GF_RECAPTCHA instance.
	 */
	protected $addon;

	/**
	 * The Google Cloud Project ID.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	protected $project_id;

	/**
	 * Constructor for RECAPTCHA_API
	 *
	 * @param string       $auth_data The array of auth data.
	 * @param GF_RECAPTCHA $addon     The GF_RECAPTCHA instance.
	 */
	public function __construct( $auth_data = null, $addon = null ) {
		$this->addon         = $addon;
		$this->refresh_token = rgar( $auth_data, 'refresh_token' );
		$this->access_token  = rgar( $auth_data, 'access_token' );
		$this->project_id    = rgar( $auth_data, 'project_id' );
	}

	/**
	 * Get Gravity API URL for path.
	 *
	 * @since 1.7.0
	 *
	 * @param string $path Endpoint path.
	 *
	 * @return string URL for Gravity API endpoint.
	 */
	public static function get_gravity_api_url( $path = '' ) {

		if ( '/' !== substr( $path, 0, 1 ) ) {
			$path = '/' . $path;
		}

		return defined( 'GRAVITY_API_URL' ) ? GRAVITY_API_URL . '/auth/googlerecaptcha' . $path : self::$gravity_api_url . '/auth/googlerecaptcha' . $path;
	}

	/**
	 * Get the base URL for the Recaptcha API.
	 *
	 * @since 1.7.0
	 *
	 * @param string $base_path The base path.
	 *
	 * @return string
	 */
	private function get_base_url( $base_path = null ) {
		if ( $base_path ) {
			return $base_path;
		}

		return 'https://recaptchaenterprise.googleapis.com/v1/';
	}

	/**
	 * Make a request to the reCAPTCHA API.
	 *
	 * @param string $path      The relative request path.
	 * @param array  $body      The request body.
	 * @param array  $headers   The request headers.
	 * @param string $method    The request method.
	 * @param string $base_path The base API path.
	 *
	 * @since 1.7.0
	 *
	 * @return array|string|WP_Error
	 */
	private function make_request( $path = '', $body = array(), $headers = array(), $method = 'GET', $base_path = null ) {

		gf_recaptcha()->log_debug( __METHOD__ . '(): Making request to: ' . $path );

		// Build request URL.
		$request_url = $this->get_base_url( $base_path ) . $path;

		$args = array(
			'method'    => $method,
			/**
			 * Filters if SSL verification should occur.
			 *
			 * @param bool   $ssl_verify  If the SSL certificate should be verified. Defaults to false.
			 * @param string $request_url The request URL.
			 *
			 * @return bool
			 */
			'sslverify' => apply_filters( 'https_local_ssl_verify', false, $request_url ),
			/**
			 * Sets the HTTP timeout, in seconds, for the request.
			 *
			 * @param int    $timeout_value The timeout limit, in seconds. Defaults to 30.
			 * @param string $request_url   The request URL.
			 *
			 * @return int
			 */
			'timeout'   => apply_filters( 'http_request_timeout', 30, $request_url ),
		);

		$args['headers'] = $headers;

		if ( 'GET' === $method || 'POST' === $method ) {
			$args['body'] = empty( $body ) ? '' : $body;
		}

		if ( 'POST' === $method ) {
			$args['body'] = wp_json_encode( $body );
		}

		// Execute request.
		$response = wp_remote_request(
			$request_url,
			$args
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body           = wp_remote_retrieve_body( $response );
		$retrieved_response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $retrieved_response_code ) {
			$error_message = rgars( $response_body, 'error/message', "Expected response code: 200. Returned response code: {$retrieved_response_code}." );
			$error_code    = rgars( $response_body, 'error/errors/reason', 'google_recaptcha_api_error' );

			gf_recaptcha()->log_error( __METHOD__ . '(): Unable to validate with the Google Cloud API: ' . $error_message );

			return new WP_Error( $error_code, $error_message, $retrieved_response_code );
		}

		return $response_body;
	}

	/**
	 * Get the result of token verification from the Recaptcha API.
	 *
	 * @param string $token  The token to verify.
	 * @param string $secret The site's secret key.
	 *
	 * @return array|\WP_Error
	 */
	public function verify_token( $token, $secret ) {
		return wp_remote_post(
			$this->verification_url,
			array(
				'body' => array(
					'secret'   => $secret,
					'response' => $token,
				),
			)
		);
	}

	/**
	 * Create the reCAPTCHA enterprise assessment.
	 *
	 * @param string $access_token The OAuth access token.
	 * @param string $project_id   The Google Cloud Project ID.
	 * @param string $token        The reCAPTCHA token from the submission.
	 * @param string $site_key     The reCAPTCHA site key.
	 * @param string $action       Teh reCATPCHA action.
	 *
	 * @since 1.7.0
	 *
	 * @return array|mixed|WP_Error
	 */
	public function create_recaptcha_assessment( $access_token, $project_id, $token, $site_key, $action ) {

		$url = 'https://recaptchaenterprise.googleapis.com/v1/projects/' . $project_id . '/assessments';

		$payload = wp_json_encode(
			array(
				'event' => array(
					'token'          => $token,
					'siteKey'        => $site_key,
					'expectedAction' => $action,
				),
			)
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => $payload,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body;
	}

	/**
	 * Refresh the reCAPTCHA access token.
	 *
	 * @param string $refresh_token The refresh token.
	 *
	 * @since 1.7.0
	 *
	 * @return array|WP_Error
	 */
	public function refresh_token( $refresh_token ) {
		// Connect to Gravity Form's API.
		$response = wp_remote_post(
			$this->get_gravity_api_url( 'refresh' ),
			array(
				'body' => array(
					'refresh_token' => rawurlencode( $refresh_token ),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$response_body = gf_recaptcha()->maybe_decode_json( wp_remote_retrieve_body( $response ) );
		$auth_payload  = json_decode( rgar( $response_body, 'auth_payload' ), true );

		$retrieved_response_code = $response['response']['code'];
		if ( 200 !== absint( $retrieved_response_code ) || ! rgar( $auth_payload, 'access_token' ) ) {
			$error_message = "Expected response code: 200. Returned response code: {$retrieved_response_code}.";

			return new WP_Error( 'google_recaptcha_api_error', $error_message, $retrieved_response_code );
		}
		return $response_body;
	}

	/**
	 * Get a list of Google Cloud projects.
	 *
	 * @since 1.7.0
	 *
	 * @return array|mixed
	 */
	public function get_recaptcha_projects() {

		$request_path = 'projects';

		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
			'Accept'        => 'application/json',
		);

		$response = $this->make_request( $request_path, array(), $headers, 'GET', 'https://cloudresourcemanager.googleapis.com/v1/' );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$data = json_decode( $response, true );

		return $data;
	}

	/**
	 * Get a list of enterprise site keys associates with the chosen project.
	 *
	 * @param string $project The Google Cloud Project ID.
	 *
	 * @since 1.7.0
	 * @since 1.8.0 Increased the default page size to 100 and added pagination to retrieve all keys.
	 *
	 * @return array|mixed
	 */
	public function get_enterprise_site_keys( $project ) {
		$request_path_base = 'projects/' . $project . '/keys';

		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
			'Accept'        => 'application/json',
		);

		$all_keys   = array(
			'keys' => array(),
		);
		$page_token = null;
		$page_size  = apply_filters( 'gform_recaptcha_enterprise_keys_page_size', 100 );

		$query_params = array(
			'pageSize' => $page_size,
		);

		do {
			if ( $page_token ) {
				$query_params['pageToken'] = $page_token;
			}

			$request_path = $request_path_base . '?' . http_build_query( $query_params );

			$response = $this->make_request( $request_path, array(), $headers );

			if ( is_wp_error( $response ) ) {
				$this->addon->log_error( __METHOD__ . '(): Unable to retrieve site keys: ' . $response->get_error_message() );
				break;
			}

			$data = json_decode( $response, true );

			if ( isset( $data['keys'] ) && is_array( $data['keys'] ) ) {
				$all_keys['keys'] = array_merge( $all_keys['keys'], $data['keys'] );
			}

			$page_token = rgar( $data, 'nextPageToken' ) ? $data['nextPageToken'] : null;
		} while ( $page_token );

		return $all_keys;
	}
 }
