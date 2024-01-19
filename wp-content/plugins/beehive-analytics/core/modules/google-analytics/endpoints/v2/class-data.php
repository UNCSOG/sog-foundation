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

namespace Beehive\Core\Modules\Google_Analytics\Endpoints\V2;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use Beehive\Core\Utils\Abstracts\Endpoint;
use Beehive\Core\Modules\Google_Analytics;

/**
 * Class Stats
 *
 * @package Beehive\Core\Modules\Google_Analytics\Endpoints\V2
 */
class Data extends Endpoint {

	/**
	 * API endpoint version.
	 *
	 * @since 3.4.0
	 *
	 * @var int $version
	 */
	protected $version = 2;

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
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public function register_routes() {
		// Route to get post stats.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/streams/',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'streams' ),
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
	}

	/**
	 * Get the list of streams for GA4 account.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function streams( $request ) {
		// Get the total count of items required.
		$network = $this->get_param( $request, 'network', false );

		$streams = Google_Analytics\Data::instance()->streams( $network );

		// Send response.
		return $this->get_response( array_values( $streams ) );
	}
}
