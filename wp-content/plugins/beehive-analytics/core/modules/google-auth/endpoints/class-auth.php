<?php
/**
 * Common action functionality REST endpoint.
 *
 * @link       http://wpmudev.com
 * @since      3.2.0
 *
 * @author     Joel James <joel@incsub.com>
 * @package    Beehive\Core\Modules\Google_Auth\Endpoints
 */

namespace Beehive\Core\Modules\Google_Auth\Endpoints;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Exception;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use Beehive\Core\Modules\Google_Auth;
use Beehive\Core\Utils\Abstracts\Endpoint;

/**
 * Class Auth
 *
 * @package Beehive\Core\Modules\Google_Auth\Endpoints
 */
class Auth extends Endpoint {

	/**
	 * API endpoint for the current endpoint.
	 *
	 * @since 3.2.4
	 *
	 * @var string $endpoint
	 */
	private $endpoint = '/auth';

	/**
	 * Register the routes for handling auth functionality.
	 *
	 * All custom routes for the stats functionality should be registered
	 * here using register_rest_route() function.
	 *
	 * @since 3.2.4
	 *
	 * @return void
	 */
	public function register_routes() {
		// Route to logout.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/logout',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'logout' ),
					'permission_callback' => array( $this, 'settings_permission' ),
					'args'                => array(
						'network' => array(
							'required'    => false,
							'description' => __( 'Network flag', 'ga_trans' ),
							'type'        => 'boolean',
						),
					),
				),
			)
		);

		// Route to get auth url.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/auth-url',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'auth_url' ),
					'permission_callback' => array( $this, 'settings_permission' ),
					'args'                => array(
						'client_id'     => array(
							'required'    => true,
							'description' => __( 'The client ID from Google API project.', 'ga_trans' ),
							'type'        => 'string',
						),
						'client_secret' => array(
							'required'    => true,
							'description' => __( 'The client secret from Google API project.', 'ga_trans' ),
							'type'        => 'string',
						),
						'context'       => array(
							'required'    => false,
							'description' => __( 'Where are you going to use the auth URL (default "settings").', 'ga_trans' ),
							'type'        => 'string',
							'enum'        => array(
								'settings',
								'dashboard',
							),
						),
						'network'       => array(
							'required'    => false,
							'description' => __( 'Network flag', 'ga_trans' ),
							'type'        => 'boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Logout Google from current site.
	 *
	 * You won't be able to access stats if you login again.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @since 3.2.4
	 *
	 * @return WP_REST_Response
	 */
	public function logout( $request ) {
		// Network flag.
		$network = (bool) $this->get_param( $request, 'network' );

		// Logout from the site.
		Google_Auth\Auth::instance()->logout( $network );

		// Send response.
		return $this->get_response(
			array(
				'message' => __( 'You have been successfully logged out.', 'ga_trans' ),
			)
		);
	}

	/**
	 * Get authentication url to redirect the user.
	 *
	 * If authentication is required, we will generate a
	 * authentication url for the user. We will store our
	 * custom data in state data to identify the callback
	 * request from Google.
	 * We will save the client id and secret first.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @since 3.2.4
	 *
	 * @return WP_REST_Response
	 */
	public function auth_url( $request ) {
		// Network flag.
		$network = (bool) $request->get_param( 'network' );
		// Client ID.
		$client_id = $request->get_param( 'client_id' );
		// Client secret.
		$client_secret = $request->get_param( 'client_secret' );

		// Both client id and client secret.
		if ( ! empty( $client_id ) && ! empty( $client_secret ) ) {
			// Save credentials.
			Google_Auth\Actions::instance()->save_settings(
				'google',
				array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
				),
				$network
			);

			// Custom data.
			$data = array(
				'page' => $this->get_param( $request, 'context', 'settings' ),
			);

			// Auth url.
			$auth_url = Google_Auth\Helper::instance()->auth_url( $network, false, false, $data );

			// Send response.
			if ( ! empty( $auth_url ) ) {
				return $this->get_response( array( 'url' => $auth_url ) );
			}
		}

		return $this->get_response(
			array(
				// translators: %s Support url.
				'message' => sprintf( __( 'We couldn\'t authorize your Google account. Please fill in your API information again, or connect with Google using the button below in side tab. If you\'re still stuck, please <a href="%s">contact support</a> for assistance.', 'ga_trans' ), '' ),
			),
			false
		);
	}
}
