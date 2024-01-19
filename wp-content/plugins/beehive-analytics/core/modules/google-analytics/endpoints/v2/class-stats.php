<?php
/**
 * GA4 Stats functionality REST endpoint.
 *
 * @link       http://wpmudev.com
 * @since      3.4.0
 *
 * @author     Joel James <joel@incsub.com>
 * @package    Beehive\Core\Modules\Google_Analytics\Endpoints\V2
 */

namespace Beehive\Core\Modules\Google_Analytics\Endpoints\V2;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use WP_REST_Request;
use WP_REST_Response;
use Beehive\Core\Modules\Google_Analytics;
use Beehive\Core\Modules\Google_Analytics\Endpoints\V1;

/**
 * Class Stats
 *
 * @package Beehive\Core\Modules\Google_Analytics\Endpoints\V2
 */
class Stats extends V1\Stats {

	/**
	 * API endpoint version.
	 *
	 * @since 3.4.0
	 *
	 * @var int $version
	 */
	protected $version = 2;

	/**
	 * Stats constructor.
	 *
	 * @since 3.4.0
	 */
	protected function __construct() {
		parent::__construct();

		// Stats instance.
		$this->stats = Google_Analytics\Stats\GA4::instance();
	}

	/**
	 * Get popular posts stats.
	 *
	 * Override parent method to get ga4 stats.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @since 3.4.0
	 *
	 * @return WP_REST_Response
	 */
	public function popular_posts( $request ) {
		// Get the total count of items required.
		$count = $this->get_param( $request, 'count', 0 );

		// Get the widget instance.
		$widget = Google_Analytics\Widgets\Popular::instance();

		// Get top posts list.
		$list = $widget->get_list( $count, false, true );

		// Send response.
		return $this->get_response( $list );
	}
}
