<?php
/**
 * The Google API request processor class.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Processors
 */

namespace Beehive\Core\Modules\Google_Analytics\Stats\Processors;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Exception;
use Beehive\Core\Modules\Google_Auth;
use Beehive\Google\Service\AnalyticsReporting;
use Beehive\Google\Service\Exception as Google_Exception;
use Beehive\Google\Service\AnalyticsReporting\GetReportsRequest;

/**
 * Class UA
 *
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Processors
 */
class UA extends Processor {

	/**
	 * Get the reports data from Google Reporting API.
	 *
	 * Use this method only if the requested data is not available
	 * in cache and transient. This method required API request so
	 * it may slow down the page load time and frequent requests may
	 * hit the API request limits.
	 *
	 * @since 3.2.0
	 *
	 * @param array           $requests  Request objects.
	 * @param string          $account   Get current account ID.
	 * @param \Exception|bool $exception Exception if any.
	 * @param bool            $network   Network flag.
	 *
	 * @return array
	 */
	protected function process_requests( $requests, $account = '', &$exception = false, $network = false ) {
		$full_reports = array();

		// Make sure we don't break anything.
		try {
			$request_count = count( $requests );
			$request_data  = array_values( $requests );

			// Maximum 5 requests can be processed in a time.
			for ( $i = 0; $i < $request_count; $i += 5 ) {
				// Split requests into batches.
				$request_batch = array_slice( $request_data, $i, 5 );

				// Create GetReportsRequest object.
				$body = new GetReportsRequest();

				// Set batch requests.
				$body->setReportRequests( $request_batch );

				// Get reports data.
				$reports = $this->analytics->reports->batchGet( $body )->getReports();

				if ( ! empty( $reports ) ) {
					$full_reports = array_merge( $full_reports, $reports );
				}
			}

			// Reset API error because it's working now.
			beehive_analytics()->settings->update( 'api_error', false, 'google', $network );
		} catch ( Google_Exception $e ) {
			// Oh well, failed.
			$full_reports = array();

			// Perform error actions.
			$this->error( $e, $network );

			$exception = $e;
		} catch ( Exception $e ) {
			// Oh well, failed generally.
			$full_reports = array();

			// Perform error actions.
			$this->error( $e, $network );

			$exception = $e;
		}

		/**
		 * Filter the Google reports API data.
		 *
		 * @since 3.2.0
		 *
		 * @param array $data API data.
		 */
		return apply_filters( 'beehive_google_stats_api_data', $full_reports );
	}

	/**
	 * Setup all required things for the API request.
	 *
	 * API request require a valid analytics class instance.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $network Network flag.
	 */
	public function setup( $network = false ) {
		parent::setup( $network );

		// New analytics instance.
		$this->analytics = new AnalyticsReporting(
			Google_Auth\Auth::instance()->client()
		);
	}
}
