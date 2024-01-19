<?php
/**
 * The Google API request setup class.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Requests
 */

namespace Beehive\Core\Modules\Google_Analytics\Stats\Requests;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Helpers\General;
use Beehive\Core\Utils\Abstracts\Base;
use Beehive\Core\Modules\Google_Analytics\Data;
use Beehive\Core\Modules\Google_Analytics\Helper;
use Beehive\Google\Service\AnalyticsReporting\Metric;
use Beehive\Google\Service\AnalyticsReporting\OrderBy;
use Beehive\Google\Service\AnalyticsReporting\Dimension;
use Beehive\Google\Service\AnalyticsReporting\DateRange;
use Beehive\Google\Service\AnalyticsData\RunReportRequest;
use Beehive\Google\Service\AnalyticsReporting\ReportRequest;
use Beehive\Google\Service\AnalyticsReporting\DimensionFilterClause;

/**
 * Class Request
 *
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Requests
 */
abstract class Request extends Base {

	/**
	 * GA account id to get reports for.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	protected $account;

	/**
	 * Get dimension based on the period.
	 *
	 * @since 3.2.0
	 *
	 * @var string $period_dimension
	 */
	protected $period_dimension = 'date';

	/**
	 * Current period's date range object.
	 *
	 * @since 3.2.0
	 *
	 * @var DateRange $current_period
	 */
	protected $current_period;

	/**
	 * Previous period's date range object.
	 *
	 * @since 3.2.0
	 *
	 * @var DateRange $previous_period
	 */
	protected $previous_period;

	/**
	 * Network flag for the request.
	 *
	 * @since 3.2.0
	 *
	 * @var bool $network
	 */
	protected $network = false;

	/**
	 * Request constructor.
	 *
	 * @since 3.2.0
	 */
	public function __construct() {
		// Make sure the autoloader is ready.
		General::vendor_autoload();
	}

	/**
	 * Set API requests based on the stats type.
	 *
	 * Different stat types required different data.
	 *
	 * @param string $type    Stats type (stats, dashboard, front).
	 * @param string $from    Start date.
	 * @param string $to      End date.
	 * @param bool   $network Network flag.
	 *
	 * @since 3.2.0
	 *
	 * @return array $requests
	 */
	public function get( $type, $from, $to, $network = false ) {
		$requests = array();

		$this->network         = $network;
		$this->current_period  = $this->get_period( $from, $to );
		$this->previous_period = $this->get_previous_period( $from, $to );

		switch ( $type ) {
			// Stats page.
			case 'stats':
				$requests = array(
					// These stats will have multiple date range values.
					'multiple' => array(
						'summary'   => $this->summary(),
						'top_pages' => $this->top_pages(),
					),
					// Current date range stats.
					'current'  => array(
						'top_countries'    => $this->top_countries(),
						'mediums'          => $this->mediums(),
						'search_engines'   => $this->search_engines(),
						'social_network'   => $this->social_networks(),
						'sessions'         => $this->sessions(),
						'users'            => $this->users(),
						'pageviews'        => $this->pageviews(),
						'page_sessions'    => $this->page_sessions(),
						'average_sessions' => $this->average_sessions(),
						'bounce_rates'     => $this->bounce_rates(),
					),
					// Previous date range stats.
					'previous' => array(
						'sessions'         => $this->sessions( false ),
						'users'            => $this->users( false ),
						'pageviews'        => $this->pageviews( false ),
						'page_sessions'    => $this->page_sessions( false ),
						'average_sessions' => $this->average_sessions( false ),
						'bounce_rates'     => $this->bounce_rates( false ),
					),
				);
				break;
			// Dashboard widget.
			case 'dashboard':
				$requests = array(
					// These stats will have multiple date range values.
					'multiple' => array(
						'summary'   => $this->summary(),
						'top_pages' => $this->top_pages( 5 ),
					),
					// Current date range stats.
					'current'  => array(
						'top_countries'    => $this->top_countries( true, 5 ),
						'mediums'          => $this->mediums(),
						'search_engines'   => $this->search_engines(),
						'social_networks'  => $this->social_networks(),
						'sessions'         => $this->sessions(),
						'users'            => $this->users(),
						'pageviews'        => $this->pageviews(),
						'page_sessions'    => $this->page_sessions(),
						'average_sessions' => $this->average_sessions(),
						'bounce_rates'     => $this->bounce_rates(),
					),
				);
				break;
			// Popular posts widget.
			case 'popular_widget':
				$requests = array(
					'current' => array( 'popular_pages' => $this->popular_pages() ),
				);
				break;

			case 'summary':
				$requests = array(
					'multiple' => array( 'summary' => $this->summary() ),
					'current'  => array(
						'popular_pages'  => $this->popular_pages( 1 ),
						'search_engines' => $this->search_engines( true, 1 ),
						'mediums'        => $this->mediums( true, 1 ),
					),
				);
		}

		return $requests;
	}

	/**
	 * Set API requests for post meta box.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $from    Start date.
	 * @param string $to      End date.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest[]|RunReportRequest[]
	 */
	abstract public function post( $post_id, $from, $to );

	/**
	 * Set API request for summary stats.
	 *
	 * Summary stats request is using 2 date ranges.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function summary();

	/**
	 * Set API request for popular pages widget stats.
	 *
	 * This is not required in network admin.
	 *
	 * @param int $page_size Page size.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function popular_pages( $page_size = 0 );

	/**
	 * Set API request for top visited pages stats.
	 *
	 * @param int $page_size Page size.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function top_pages( $page_size = 25 );

	/**
	 * Set API request for the top visited countries.
	 *
	 * @param bool $current   Is this request is for current period.
	 * @param int  $page_size Page size.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function top_countries( $current = true, $page_size = 25 );

	/**
	 * Set API request for the mediums stats.
	 *
	 * @param bool $current   Is this request is for current period.
	 * @param int  $page_size Page size.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function mediums( $current = true, $page_size = 0 );

	/**
	 * Set API request for the social network stats.
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function social_networks( $current = true );

	/**
	 * Set API request for sessions stats.
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function sessions( $current = true );

	/**
	 * Set API request for the search engine traffic stats.
	 *
	 * @param bool $current   Is this request is for current period.
	 * @param int  $page_size Page size.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function search_engines( $current = true, $page_size = 0 );

	/**
	 * Set API request for the users list stats.
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function users( $current = true );

	/**
	 * Set API request for the pageviews stats list.
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function pageviews( $current = true );

	/**
	 * Set API request for the page sessions stats list.
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function page_sessions( $current = true );

	/**
	 * Set API request for the average sessions list.
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function average_sessions( $current = true );

	/**
	 * Set API request for the bounce rates list.
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest|RunReportRequest
	 */
	abstract public function bounce_rates( $current = true );

	/**
	 * Get reports metrics for API request.
	 *
	 * You can query multiple metrics by passing metric name as an array.
	 * Do not append ga: prefix, it will be handled within the method.
	 * To get list of items see:
	 * https://developers.google.com/analytics/devguides/reporting/core/dimsmets
	 *
	 * @param array  $metrics Metric types.
	 * @param string $alias   Custom alias base name.
	 *
	 * @since 3.2.0
	 *
	 * @return Metric[]
	 */
	abstract public function get_metrics( $metrics = array(), $alias = 'beehive' );

	/**
	 * Set reports dimensions for API request.
	 *
	 * You can query multiple dimensions by passing metric name as an array.
	 * Do not append ga: prefix, it will be handled within the method.
	 * To get list of items see:
	 * https://developers.google.com/analytics/devguides/reporting/core/dimsmets
	 *
	 * @param array $dimensions Dimension types.
	 *
	 * @since 3.2.0
	 *
	 * @return Dimension[]
	 */
	abstract public function get_dimensions( $dimensions = array() );

	/**
	 * Set reports sorting to filter results.
	 *
	 * You can sort using multiple fields. To get list of items see
	 * https://developers.google.com/analytics/devguides/reporting/core/dimsmets
	 *
	 * @param array $fields Fields to sort based on.
	 *
	 * @since 3.2.0
	 *
	 * @return OrderBy[]
	 */
	abstract public function get_orders( $fields = array() );

	/**
	 * Set reports dimensions filter.
	 *
	 * Do not append ga: to field names.
	 *
	 * @param array  $filter_params Filter items.
	 * @param string $operator      Operator (OR or AND).
	 *
	 * @since 3.2.0
	 *
	 * @return DimensionFilterClause
	 */
	abstract public function get_dimension_filter( $filter_params, $operator = 'AND' );

	/**
	 * Setup reporting period to get stats data.
	 *
	 * Only allowed periods will be processed. For other periods you
	 * can use custom type and pass the from and to dates.
	 *
	 * @param string $from Start date.
	 * @param string $to   End date.
	 *
	 * @since 3.2.0
	 *
	 * @return DateRange
	 */
	abstract public function get_period( $from, $to );

	/**
	 * Setup reporting period to get stats data.
	 *
	 * Only allowed periods will be processed. For other periods you
	 * can use custom type and pass the from and to dates.
	 *
	 * @param string $from Start date.
	 * @param string $to   End date.
	 *
	 * @since 3.2.0
	 *
	 * @return DateRange
	 */
	abstract public function get_previous_period( $from, $to );

	/**
	 * Set url filter for the single site stats.
	 *
	 * When stats are loaded using the login from network
	 * setup, we need to show stats only for the currently viewing
	 * single site.
	 *
	 * @since 3.2.0
	 *
	 * @return array
	 */
	abstract protected function get_url_filter();

	/**
	 * Setup GA account string for the reports data.
	 *
	 * @param bool $network Network flag.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public function setup_account( $network = false ) {
		// Decide login source.
		$network = Helper::instance()->login_source( $network ) === 'network';

		// Get currently assigned id.
		$account = beehive_analytics()->settings->get( 'account_id', 'google', $network );

		// Some times, account id can be empty when user didn't save the settings. We need to handle this ourselves.
		if ( empty( $account ) ) {
			// Get available profiles.
			$profiles = Data::instance()->profiles( $network );
			if ( ! empty( $profiles ) ) {
				// Set the first item for now.
				$account = $profiles[0]->getId();
				// Update the settings.
				beehive_analytics()->settings->update( 'account_id', $account, 'google', $network );
			}
		}

		$this->account = $account;
	}

	/**
	 * Get current set account ID.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_account() {
		return $this->account;
	}

	/**
	 * Get dimension based on the period set.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_period_dimension() {
		return $this->period_dimension;
	}
}
