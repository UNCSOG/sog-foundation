<?php
/**
 * Stats functionality REST endpoint.
 *
 * @link       http://wpmudev.com
 * @since      3.2.0
 *
 * @author     Joel James <joel@incsub.com>
 * @package    Beehive\Core\Modules\Google_Analytics\Endpoints
 */

namespace Beehive\Core\Modules\Google_Analytics\Endpoints\V1;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Beehive\Core\Utils\Abstracts\Endpoint;
use Beehive\Core\Modules\Google_Analytics;

/**
 * Class Stats
 *
 * @package Beehive\Core\Modules\Google_Analytics\Endpoints\V1
 */
class Data extends Endpoint {

	/**
	 * API endpoint version.
	 *
	 * @since 3.4.0
	 *
	 * @var int $version
	 */
	protected $version = 1;

	/**
	 * API endpoint for the current endpoint.
	 *
	 * @since 3.4.0
	 *
	 * @var string $endpoint
	 */
	private $endpoint = '/data';

	/**
	 * Register the routes for handling settings functionality.
	 *
	 * All custom routes for the stats functionality should be registered
	 * here using register_rest_route() function.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public function register_routes() {
		// Route to get google accounts.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/accounts/',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'accounts' ),
					'permission_callback' => array( $this, 'public_permission' ),
					'args'                => array(
						'network' => array(
							'required'    => false,
							'type'        => 'boolean',
							'description' => __( 'The network flag.', 'ga_trans' ),
						),
					),
				),
			)
		);
		// Route to get properties of selected account.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/properties/',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'properties' ),
					'permission_callback' => array( $this, 'public_permission' ),
					'args'                => array(
						'account' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => __( 'The selected account ID.', 'ga_trans' ),
						),
						'network' => array(
							'required'    => false,
							'type'        => 'boolean',
							'description' => __( 'The network flag.', 'ga_trans' ),
						),
					),
				),
			)
		);
		// Route to get post stats.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/streams/',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'streams' ),
					'permission_callback' => array( $this, 'public_permission' ),
					'args'                => array(
						'property' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => __( 'The selected property ID.', 'ga_trans' ),
						),
						'network'  => array(
							'required'    => false,
							'type'        => 'boolean',
							'description' => __( 'The network flag.', 'ga_trans' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Get the list of streams for selected property.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 3.4.0
	 */
	public function streams( $request ) {
		// Get the total count of items required.
		$network  = $this->get_param( $request, 'network', false );
		$token    = $this->get_param( $request, 'pageToken', '' );
		$property = $this->get_param( $request, 'property', false );

		if ( empty( $property ) ) {
			return new WP_Error(
				'missing_property',
				__( 'Property parameter is required.', 'ga_trans' ),
				array( 'status' => 400 )
			);
		}

		$streams = Google_Analytics\Data::instance()->paginated_data(
			array(
				'type'    => Google_Analytics\Data::TYPE_STREAMS,
				'token'   => $token,
				'filter'  => $property,
				'network' => $network,
			)
		);

		// Send response.
		return new WP_REST_Response( $streams );
	}

	/**
	 * Handles the request to retrieve Google Analytics accounts.
	 *
	 * @param WP_REST_Request $request The request object containing parameters.
	 *
	 * @return WP_REST_Response|WP_Error The response object containing account data or an error object.
	 */
	public function accounts( $request ) {
		// Get the total count of items required.
		$network  = $this->get_param( $request, 'network', false );
		$token    = $this->get_param( $request, 'pageToken', '' );
		$accounts = Google_Analytics\Data::instance()->paginated_data(
			array(
				'type'    => Google_Analytics\Data::TYPE_ACCOUNTS,
				'token'   => $token,
				'network' => $network,
			)
		);

		// Send response.
		return new WP_REST_Response( $accounts );
	}

	/**
	 * Handles the request to retrieve Google Analytics properties for a specific account.
	 *
	 * @param WP_REST_Request $request The request object containing parameters.
	 *
	 * @return WP_REST_Response|WP_Error The response object containing property data or an error object.
	 */
	public function properties( $request ) {
		$network = $this->get_param( $request, 'network', false );
		$account = $this->get_param( $request, 'account', false );
		$token   = $this->get_param( $request, 'pageToken', '' );
		if ( empty( $account ) ) {
			return new WP_Error(
				'missing_account',
				__( 'Account parameter is required.', 'ga_trans' ),
				array( 'status' => 400 )
			);
		}

		$properties = Google_Analytics\Data::instance()->paginated_data(
			array(
				'type'    => Google_Analytics\Data::TYPE_PROPERTIES,
				'token'   => $token,
				'filter'  => $account,
				'network' => $network,
			)
		);

		// Send response.
		return new WP_REST_Response( $properties );
	}
}
