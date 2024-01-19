<?php
/**
 * The Google API setup class.
 *
 * @link    http://wpmudev.com
 * @since   3.4.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Processors
 */

namespace Beehive\Core\Modules\Google_Analytics\Stats\Processors;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Modules\Google_Auth;
use Beehive\Google\Service\AnalyticsData;
use Beehive\Core\Utils\Abstracts\Google_API;
use Beehive\Google\Service\AnalyticsReporting;
use Beehive\Core\Modules\Google_Analytics\Helper;

/**
 * Class Processor
 *
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Processors
 */
abstract class Processor extends Google_API {

	/**
	 * Google Analytics class instance.
	 *
	 * @since 3.4.0
	 * @var AnalyticsReporting|AnalyticsData
	 */
	protected $analytics;

	/**
	 * Get the multiple reports from Google Reporting API.
	 *
	 * Multiple Date range requests should be made as different
	 * request. So this method will handle that.
	 *
	 * @since 3.4.0
	 *
	 * @param array           $request_types Report request array.
	 * @param bool            $network       Network flag.
	 * @param string          $account       Get current account ID.
	 * @param \Exception|bool $exception     Exception if any.
	 *
	 * @return array
	 */
	public function process_request_types( $request_types = array(), $network = false, $account = '', &$exception = false ) {
		// Decide login source.
		$network = Helper::instance()->login_source( $network ) === 'network';

		// Setup login.
		$this->setup( $network );

		$full_reports = array();

		// Process each request types (different date ranges).
		foreach ( $request_types as $type => $requests ) {
			$full_reports[ $type ] = $this->process_requests( $requests, $account, $exception, $network );
		}

		/**
		 * Filter the Google reports API raw data.
		 *
		 * @since 3.2.0
		 *
		 * @param array $data API data.
		 */
		return apply_filters( 'beehive_google_stats_api_full_data', $full_reports );
	}

	/**
	 * Get the reports data from Google Reporting API.
	 *
	 * Use this method only if the requested data is not available
	 * in cache and transient. This method required API request so
	 * it may slow down the page load time and frequent requests may
	 * hit the API request limits.
	 *
	 * @since 3.4.0
	 *
	 * @param array           $requests  Request objects.
	 * @param string          $account   Get current account ID.
	 * @param \Exception|bool $exception Exception if any.
	 * @param bool            $network   Network flag.
	 *
	 * @return array
	 */
	abstract protected function process_requests( $requests, $account = '', &$exception = false, $network = false );

	/**
	 * Setup all required things for the API request.
	 *
	 * API request require a valid analytics class instance.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $network Network flag.
	 */
	public function setup( $network = false ) {
		// Set auth data.
		Google_Auth\Helper::instance()->setup_auth( $network );
	}
}
