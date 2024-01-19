<?php
/**
 * The Google authentication class.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Auth
 */

namespace Beehive\Core\Modules\Google_Auth;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Helpers\General;
use Beehive\Core\Helpers\Template;
use Beehive\Core\Utils\Abstracts\Base;

/**
 * Class Actions
 *
 * @package Beehive\Core\Modules\Google_Auth
 */
class Actions extends Base {

	/**
	 * Initialize the class by registering hooks.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function init() {
		// Handle Google auth callback.
		add_action( 'init', array( $this, 'handle_callback' ) );

		// Handle Google auth callback.
		add_action( 'admin_init', array( $this, 'exchange_code' ) );
	}

	/**
	 * Handle Google authentication callback.
	 *
	 * Check if current request has the Google callback params.
	 * If so, validate the request and redirect to our plugin
	 * page to process the access code.
	 *
	 * @since 3.2.0
	 * @since 3.4.1 Added new method.
	 *
	 * @return void
	 */
	public function handle_callback() {
		// Make sure this is Google callback.
		if ( isset( $_GET['state'], $_GET['code'] ) || isset( $_GET['state'], $_GET['error'] ) ) { // phpcs:ignore
			// Decode the state data.
			$state = json_decode( rawurldecode( $_GET['state'] ), true ); // phpcs:ignore
			if ( isset( $state['beehive_nonce'], $state['origin'], $state['default'], $state['page'] ) ) {
				// phpcs:disable
				$data = array(
					'gcode'         => isset( $_GET['code'] ) ? $_GET['code'] : 0,
					'error'         => isset( $_GET['error'] ) ? $_GET['error'] : '',
					'default'       => 0,
					'client'        => 'custom', // Just to bypass.
					'beehive_nonce' => $state['beehive_nonce'],
				);
				// phpcs:enable
				// Setup redirect.
				$this->redirect_for_exchange( $state['origin'], $state['page'], $data );
			}
		} elseif ( isset( $_GET['beehive_nonce'], $_GET['origin'], $_GET['default'], $_GET['page'], $_GET['client'] ) ) { // phpcs:ignore
			// phpcs:disable
			$data = array(
				'gcode'         => isset( $_GET['code'] ) ? $_GET['code'] : 0,
				'error'         => isset( $_GET['error'] ) ? $_GET['error'] : '',
				'default'       => $_GET['default'],
				'client'        => $_GET['client'],
				'beehive_nonce' => $_GET['beehive_nonce'],
			);
			// phpcs:enable
			// Setup redirect.
			$this->redirect_for_exchange( $_GET['origin'], $_GET['page'], $data ); // phpcs:ignore
		}
	}

	/**
	 * Handle Google authentication callback redirect.
	 *
	 * Handle the redirect with Google access code from
	 * authentication callback. This is not the callback
	 * from Google. We have been redirected to here from
	 * handle_callback() method using access code.
	 * We need to exchange the access code with Google and
	 * get the access token.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function exchange_code() {
		$success = false;

		$core = Auth::instance();

		// Continue only when required data is set.
		if ( ! isset( $_GET['gcode'], $_GET['beehive_nonce'], $_GET['default'], $_GET['client'] ) ) { // phpcs:ignore
			return;
		}

		// Security check.
		// phpcs:ignore
		if ( ! wp_verify_nonce( $_GET['beehive_nonce'], 'beehive_nonce' ) ) {
			return;
		}

		// Check if the authentication is using default credentials.
		// phpcs:ignore
		$default = ! empty( $_GET['default'] );

		// Network flag.
		$network = is_network_admin();

		// Continue only if valid code found.
		// phpcs:ignore
		if ( ! empty( $_GET['gcode'] ) && ! empty( $_GET['client'] ) ) {
			$client_id = $_GET['client']; // phpcs:ignore

			// Setup client instance.
			$default ? $core->setup_default( $network, $client_id ) : $core->setup( $network );

			// Sanitize the code.
			// phpcs:ignore
			$g_code = sanitize_text_field( $_GET['gcode'] );

			// Exchange access code and get access token.
			$token = $core->client()->fetchAccessTokenWithAuthCode( $g_code );

			// Save access and refresh tokens.
			if ( isset( $token['access_token'], $token['refresh_token'] ) ) {
				// We don't need scope. It may get blocked by WAFs.
				if ( isset( $token['scope'] ) ) {
					unset( $token['scope'] );
				}

				// When we are re-using the network API creds.
				if ( ! $network && General::is_networkwide() && Helper::instance()->is_logged_in( true ) && 'api' === Helper::instance()->login_method( true ) ) {
					$method = 'network_connect';
				} else {
					$method = 'api';
				}

				// Get credentials.
				$client_secret = $this->get_secret( $client_id );
				$client_id     = empty( $client_secret ) ? '' : $client_id;
				// If default credentials are used method should be connect.
				$method = empty( $client_secret ) ? $method : 'connect';

				// Update the login data.
				$this->save_settings(
					'google_login',
					array(
						'access_token'  => wp_json_encode( $token ), // For backward compatibility.
						'logged_in'     => 2, // Logged in flag.
						'method'        => $method, // Login method.
						'name'          => '', // Clear old name.
						'email'         => '', // Clear old email.
						'photo'         => '', // Clear old photo.
						'client_id'     => $client_id,
						'client_secret' => $client_secret,
					),
					$network
				);

				// Setup user data.
				Data::instance()->user( $network );

				// Success flag.
				$success = true;
			}
		}

		// Flag to show notice.
		beehive_analytics()->settings->update(
			'google_auth_redirect_success',
			$success ? 'success' : 'error',
			'misc',
			$network
		);

		/**
		 * Hook to execute after authentication.
		 *
		 * @since 3.2.0
		 *
		 * @param bool $default Did we connect using default credentials?.
		 * @param bool $network Network flag.
		 *
		 * @param bool $success Is success or fail?.
		 */
		do_action( 'beehive_google_auth_completed', $success, $default, $network );
	}

	/**
	 * Save Google API credentials to db.
	 *
	 * We need to save only the given items and keep
	 * the existing items intact.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type    Settings type.
	 * @param array  $data    Credentials data.
	 * @param bool   $network Network flag.
	 *
	 * @return void
	 */
	public function save_settings( $type = 'google', $data = array(), $network = false ) {
		// Get available keys.
		$fields = beehive_analytics()->settings->default_settings( $network );

		// Only if valid.
		if ( isset( $fields[ $type ] ) ) {
			// Get all values first.
			$options = beehive_analytics()->settings->get_options( $type, $network, false, false );

			// Loop through each items.
			foreach ( $fields[ $type ] as $key => $value ) {
				// Make sure only allowed items are saved.
				if ( isset( $data[ $key ] ) ) {
					// Sanitize.
					if ( is_array( $data[ $key ] ) ) {
						$options[ $key ] = General::sanitize_array( $data[ $key ] );
					} else {
						$options[ $key ] = sanitize_text_field( $data[ $key ] );
					}
				}
			}

			// Update Google data.
			beehive_analytics()->settings->update_group( $options, $type, $network );
		}
	}

	/**
	 * Self redirect to plugin page for authentication.
	 *
	 * Exchange the access code and get the access token.
	 *
	 * @since 3.4.1
	 *
	 * @param string $origin Origin.
	 * @param string $page   Plugin page type.
	 * @param array  $data   Data to pass.
	 *
	 * @return void
	 */
	private function redirect_for_exchange( $origin, $page, $data = array() ) {
		// Setup the redirect url base.
		if ( 'network' === $origin ) {
			// If from dashboard.
			if ( 'dashboard' === $page ) {
				$url = Template::dashboard_url( true );
			} else {
				$url = Template::accounts_url( 'google', true );
			}
		} else {
			// If from dashboard.
			if ( 'dashboard' === $page ) {
				$url = Template::dashboard_url();
			} else {
				$url = Template::accounts_url( 'google', false, $origin );
			}
		}

		// Setup redirect url.
		$url = add_query_arg( $data, $url );

		/**
		 * Action hook to execute after redirected from Google auth.
		 *
		 * @since 3.2.0
		 *
		 * @param string $url Redirect url.
		 */
		do_action( 'beehive_google_callback', $url );

		// Redirect to our page.
		// phpcs:ignore
		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Get the client secret for given client id.
	 *
	 * @since 3.4.1
	 *
	 * @param string $client Client ID.
	 *
	 * @return string
	 */
	private function get_secret( $client = '' ) {
		$credentials = Data::instance()->credentials();

		// Only if one of our credentials.
		if ( ! empty( $client ) && isset( $credentials[ $client ]['secret'] ) ) {
			return $credentials[ $client ]['secret'];
		}

		return '';
	}
}
