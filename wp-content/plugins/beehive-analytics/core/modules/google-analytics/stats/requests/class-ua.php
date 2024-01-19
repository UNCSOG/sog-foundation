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

use Exception;
use Beehive\Google\Service\Exception as Google_Exception;
use Beehive\Core\Modules\Google_Auth\Auth;
use Beehive\Core\Modules\Google_Analytics\Helper;
use Beehive\Google\Service\AnalyticsReporting\Metric;
use Beehive\Google\Service\AnalyticsReporting\OrderBy;
use Beehive\Google\Service\AnalyticsReporting\Dimension;
use Beehive\Google\Service\AnalyticsReporting\DateRange;
use Beehive\Google\Service\Analytics as Google_Analytics;
use Beehive\Google\Service\AnalyticsReporting\ReportRequest;
use Beehive\Google\Service\AnalyticsReporting\DimensionFilter;
use Beehive\Google\Service\AnalyticsReporting\MetricFilterClause;
use Beehive\Google\Service\AnalyticsReporting\DimensionFilterClause;
use Beehive\Core\Modules\Google_Analytics\Stats\Processors;
use Beehive\Google\Service\Analytics\RealtimeData;

/**
 * Class UA
 *
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Requests
 */
class UA extends Request {

	/**
	 * Set API request for the realtime stats and get stats.
	 *
	 * Note: This method doesn't cache anything.
	 *
	 * @since 3.3.8
	 *
	 * @param bool            $network   Network flag.
	 * @param \Exception|bool $exception Exception if any.
	 *
	 * @return RealtimeData|false
	 */
	public function realtime( $network = false, &$exception = false ) {
		// Setup account.
		$this->setup_account( $network );

		// Setup login.
		Processors\UA::instance()->setup( $network );

		// Analytics V3 instance.
		$analytics = new Google_Analytics( Auth::instance()->client() );

		try {
			// Get the stats.
			$stats = $analytics->data_realtime->get(
				'ga:' . $this->account,
				'rt:activeUsers',
				array(
					'dimensions' => 'rt:deviceCategory',
				)
			);
		} catch ( Google_Exception $e ) {
			$stats = false;

			$exception = $e;
		} catch ( Exception $e ) {
			$stats = false;

			$exception = $e;
		}

		return $stats;
	}

	/**
	 * Set API requests for post meta box.
	 *
	 * @since 3.2.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $from    Start date.
	 * @param string $to      End date.
	 *
	 * @return ReportRequest[]
	 */
	public function post( $post_id, $from, $to ) {
		$requests = array();

		// Get page permalink.
		$url = get_permalink( $post_id );

		// Only when valid post id is found.
		if ( ! empty( $url ) ) {
			// Setup dates.
			$periods = array(
				$this->get_period( $from, $to ),
				$this->get_previous_period( $from, $to ),
			);

			// If the current page is home page.
			if ( trailingslashit( network_home_url() ) === trailingslashit( $url ) ) {
				$url = '/';
			} else {
				// Remove protocol first.
				$clean_url = str_replace( array( 'http://', 'https://' ), '', $url );

				// Explode URL parts by "/".
				$url_parts = explode( '/', $clean_url );

				// Get the url part without domain name.
				if ( isset( $url_parts[0] ) ) {
					$url = str_replace( untrailingslashit( $url_parts[0] ), '', $clean_url );
				} else {
					$url = str_replace( untrailingslashit( network_home_url() ), '', $url );
				}
			}

			// Setup account.
			$this->setup_account();

			// Filter based on the post id.
			$dimension_filters[] = $this->get_dimension_filter(
				array(
					'pagePath' => array(
						'value'    => $url,
						'operator' => 'EXACT',
					),
				)
			);

			// Set summary request.
			$metrics = $this->get_metrics(
				array(
					'sessions',
					'pageviews',
					'users',
					'pageviewsPerSession',
					'avgSessionDuration',
					'bounceRate',
				),
				'summary'
			);

			$requests['multiple'] = array( $this->get_request( $periods, $metrics, array(), array(), $dimension_filters ) );
		}

		return $requests;
	}

	/**
	 * Set API request for summary stats.
	 *
	 * Summary stats request is using 2 date ranges.
	 *
	 * @since 3.2.0
	 *
	 * @return ReportRequest
	 */
	public function summary() {
		// Setup dates.
		$periods = array(
			// Current period.
			$this->current_period,
			// We need stats for the previous period also.
			$this->previous_period,
		);

		// Setup account.
		$this->setup_account( $this->network );

		// Set summary request.
		$metrics = $this->get_metrics(
			array(
				'sessions',
				'pageviews',
				'users',
				'pageviewsPerSession',
				'avgSessionDuration',
				'bounceRate',
				'percentNewSessions',
				'newUsers',
			),
			'summary'
		);

		return $this->get_request( $periods, $metrics );
	}

	/**
	 * Set API request for popular pages widget stats.
	 *
	 * This is not required in network admin.
	 *
	 * @since 3.2.0
	 *
	 * @param int $page_size Page size.
	 *
	 * @return ReportRequest
	 */
	public function popular_pages( $page_size = 0 ) {
		// Setup dates.
		$periods = array( $this->current_period );

		// Setup account.
		$this->setup_account( $this->network );

		/**
		 * Filter hook to modify no. of popular page items required.
		 *
		 * @since 3.2.0
		 *
		 * @param bool $network   Network flag.
		 *
		 * @param int  $page_size Page size (default is 0 for all).
		 */
		$page_size = apply_filters( 'beehive_google_analytics_request_popular_posts_size', $page_size, $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'pageviews' ), 'pages' );

		$dimensions = $this->get_dimensions( array( 'hostname', 'pageTitle', 'pagePath' ) );

		// Order by pageviews.
		$orders = $this->get_orders( array( 'pageviews' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, array(), array(), $page_size );
	}

	/**
	 * Set API request for top visited pages stats.
	 *
	 * @since 3.2.0
	 *
	 * @param int $page_size Page size.
	 *
	 * @return ReportRequest
	 */
	public function top_pages( $page_size = 25 ) {
		// Setup dates.
		$periods = array(
			// Current period.
			$this->current_period,
			// We need stats for the previous period also.
			$this->previous_period,
		);

		// Setup account.
		$this->setup_account( $this->network );

		/**
		 * Filter hook to modify no. of pages required.
		 *
		 * @since 3.2.4
		 *
		 * @param bool $network   Network flag.
		 *
		 * @param int  $page_size Page size (default is 0 for all).
		 */
		$page_size = apply_filters( 'beehive_google_analytics_request_top_pages_size', $page_size, $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'avgSessionDuration', 'pageviews' ), 'pages' );

		$dimensions = $this->get_dimensions( array( 'hostname', 'pageTitle', 'pagePath' ) );

		// Order by pageviews.
		$orders = $this->get_orders( array( 'pageviews' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, array(), array(), $page_size );
	}

	/**
	 * Set API request for the top visited countries.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current   Is this request is for current period.
	 * @param int  $page_size Page size.
	 *
	 * @return ReportRequest
	 */
	public function top_countries( $current = true, $page_size = 25 ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		/**
		 * Filter hook to modify no. of countries returned.
		 *
		 * @since 3.2.4
		 *
		 * @param bool $network   Network flag.
		 *
		 * @param int  $page_size Page size (default is 0 for all).
		 */
		$page_size = apply_filters( 'beehive_google_analytics_request_top_countries_size', $page_size, $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'pageviews' ), 'countries' );

		$dimensions = $this->get_dimensions( array( 'country', 'countryIsoCode' ) );

		// Order by pageviews.
		$orders = $this->get_orders( array( 'pageviews' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, array(), array(), $page_size );
	}

	/**
	 * Set API request for the mediums stats.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current   Is this request is for current period.
	 * @param int  $page_size Page size.
	 *
	 * @return ReportRequest
	 */
	public function mediums( $current = true, $page_size = 0 ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		/**
		 * Filter hook to modify no. of mediums returned.
		 *
		 * @since 3.2.4
		 *
		 * @param bool $network   Network flag.
		 *
		 * @param int  $page_size Page size (default is 0 for all).
		 */
		$page_size = apply_filters( 'beehive_google_analytics_request_mediums_size', $page_size, $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'sessions' ), 'mediums' );

		$dimensions = $this->get_dimensions( array( 'channelGrouping' ) );

		// Order by sessions.
		$orders = $this->get_orders( array( 'sessions' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, array(), array(), $page_size );
	}

	/**
	 * Set API request for the social network stats.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return ReportRequest
	 */
	public function social_networks( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'sessions' ), 'social_networks' );

		$dimensions = $this->get_dimensions( array( 'socialNetwork' ) );

		// Filter only social channels.
		$dimension_filters[] = $this->get_dimension_filter(
			array(
				'channelGrouping' => array(
					'value'    => 'Social',
					'operator' => 'EXACT',
				),
			)
		);

		// Order by sessions.
		$orders = $this->get_orders( array( 'sessions' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, $dimension_filters );
	}

	/**
	 * Set API request for sessions stats.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return ReportRequest
	 */
	public function sessions( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'sessions' ), 'sessions' );

		$period_dimension = $this->get_period_dimension();

		$dimensions = $this->get_dimensions( array( $period_dimension ) );

		return $this->get_request( $periods, $metrics, $dimensions );
	}

	/**
	 * Set API request for the search engine traffic stats.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current   Is this request is for current period.
	 * @param int  $page_size Page size.
	 *
	 * @return ReportRequest
	 */
	public function search_engines( $current = true, $page_size = 0 ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		/**
		 * Filter hook to modify no. of search engines returned.
		 *
		 * @since 3.2.4
		 *
		 * @param bool $network   Network flag.
		 *
		 * @param int  $page_size Page size (default is 0 for all).
		 */
		$page_size = apply_filters( 'beehive_google_analytics_request_search_engines_size', $page_size, $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'sessions' ), 'search_engines' );

		$dimensions = $this->get_dimensions( array( 'medium', 'source' ) );

		$dimension_filters[] = $this->get_dimension_filter(
			array(
				'medium' => array(
					'value'    => 'organic',
					'operator' => 'EXACT',
				),
			)
		);

		// Order by sessions.
		$orders = $this->get_orders( array( 'sessions' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, $dimension_filters, array(), $page_size );
	}

	/**
	 * Set API request for the users list stats.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return ReportRequest
	 */
	public function users( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'users' ), 'users' );

		$period_dimension = $this->get_period_dimension();

		$dimensions = $this->get_dimensions( array( $period_dimension ) );

		return $this->get_request( $periods, $metrics, $dimensions );
	}

	/**
	 * Set API request for the pageviews stats list.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return ReportRequest
	 */
	public function pageviews( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'pageviews' ), 'pageviews' );

		$period_dimension = $this->get_period_dimension();

		$dimensions = $this->get_dimensions( array( $period_dimension ) );

		return $this->get_request( $periods, $metrics, $dimensions );
	}

	/**
	 * Set API request for the page sessions stats list.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return ReportRequest
	 */
	public function page_sessions( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'pageviewsPerSession' ), 'page_sessions' );

		$period_dimension = $this->get_period_dimension();

		$dimensions = $this->get_dimensions( array( $period_dimension ) );

		return $this->get_request( $periods, $metrics, $dimensions );
	}

	/**
	 * Set API request for the average sessions list.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return ReportRequest
	 */
	public function average_sessions( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'avgSessionDuration' ), 'average_sessions' );

		$period_dimension = $this->get_period_dimension();

		$dimensions = $this->get_dimensions( array( $period_dimension ) );

		return $this->get_request( $periods, $metrics, $dimensions );
	}

	/**
	 * Set API request for the bounce rates list.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return ReportRequest
	 */
	public function bounce_rates( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'bounceRate' ), 'bounce_rates' );

		$period_dimension = $this->get_period_dimension();

		$dimensions = $this->get_dimensions( array( $period_dimension ) );

		return $this->get_request( $periods, $metrics, $dimensions );
	}

	/**
	 * Get reports metrics for API request.
	 *
	 * You can query multiple metrics by passing metric name as an array.
	 * Do not append ga: prefix, it will be handled within the method.
	 * To get list of items see:
	 * https://developers.google.com/analytics/devguides/reporting/core/dimsmets
	 *
	 * @since 3.2.0
	 *
	 * @param array  $metrics Metric types.
	 * @param string $alias   Custom alias base name.
	 *
	 * @return Metric[]
	 */
	public function get_metrics( $metrics = array(), $alias = 'beehive' ) {
		// Empty the metrics.
		$metrics_instances = array();

		// Set each metrics.
		foreach ( (array) $metrics as $type ) {
			// Create the Metrics object.
			$metric = new Metric();
			// Set metrics type.
			$metric->setExpression( "ga:{$type}" );
			// Set custom alias to identify the data.
			$metric->setAlias( "{$alias}:{$type}" );

			// Set to metrics instances.
			$metrics_instances[] = $metric;
		}

		return $metrics_instances;
	}

	/**
	 * Set reports dimensions for API request.
	 *
	 * You can query multiple dimensions by passing metric name as an array.
	 * Do not append ga: prefix, it will be handled within the method.
	 * To get list of items see:
	 * https://developers.google.com/analytics/devguides/reporting/core/dimsmets
	 *
	 * @since 3.2.0
	 *
	 * @param array $dimensions Dimension types.
	 *
	 * @return Dimension[]
	 */
	public function get_dimensions( $dimensions = array() ) {
		// Empty the dimensions.
		$dimension_instances = array();

		// Set each metrics.
		foreach ( (array) $dimensions as $type ) {
			// Create the Metrics object.
			$dimension = new Dimension();
			// Set dimension type.
			$dimension->setName( "ga:{$type}" );

			// Set to dimension instances.
			$dimension_instances[] = $dimension;
		}

		return $dimension_instances;
	}

	/**
	 * Set reports sorting to filter results.
	 *
	 * You can sort using multiple fields. To get list of items see
	 * https://developers.google.com/analytics/devguides/reporting/core/dimsmets
	 *
	 * @since 3.2.0
	 *
	 * @param array $fields Fields to sort based on.
	 *
	 * @return OrderBy[]
	 */
	public function get_orders( $fields = array() ) {
		// Empty the sorting.
		$orders = array();

		// Only when fields are not empty.
		if ( ! empty( $fields ) ) {
			// Set each metrics.
			foreach ( (array) $fields as $field ) {
				// Create sorting object.
				$sorting = new OrderBy();
				// Set sorting field.
				$sorting->setFieldName( "ga:{$field}" );
				// Set order.
				$sorting->setSortOrder( 'DESCENDING' );

				// Set to sorting instances.
				$orders[] = $sorting;
			}
		}

		return $orders;
	}

	/**
	 * Set reports dimensions filter.
	 *
	 * Do not append ga: to field names.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $filter_params Filter items.
	 * @param string $operator      Operator (OR or AND).
	 *
	 * @return DimensionFilterClause
	 */
	public function get_dimension_filter( $filter_params, $operator = 'AND' ) {
		// Create filter clause object.
		$filter_clause = new DimensionFilterClause();

		// Set each fields.
		foreach ( (array) $filter_params as $field => $data ) {
			// Create filter object.
			$filter = new DimensionFilter();
			// Set dimension name.
			$filter->setDimensionName( "ga:{$field}" );
			// Set value.
			$filter->setExpressions( array( $data['value'] ) );
			// Set operator.
			if ( isset( $data['operator'] ) ) {
				$filter->setOperator( $data['operator'] );
			}

			// Add to filters array.
			$filters[] = $filter;
		}

		// Set filters.
		$filter_clause->setFilters( $filters );
		// Filter operator.
		$filter_clause->setOperator( $operator );

		return $filter_clause;
	}

	/**
	 * Setup reporting period to get stats data.
	 *
	 * Only allowed periods will be processed. For other periods you
	 * can use custom type and pass the from and to dates.
	 *
	 * @since 3.2.0
	 *
	 * @param string $from Start date.
	 * @param string $to   End date.
	 *
	 * @return DateRange
	 */
	public function get_period( $from, $to ) {
		try {
			// Make sure the dates are in proper format.
			$from = gmdate( 'Y-m-d', strtotime( $from ) );
			$to   = gmdate( 'Y-m-d', strtotime( $to ) );

			// Create date objects from the periods.
			$date_from = date_create( $from );
			$date_to   = date_create( $to );
			// Get the difference between periods.
			$days = (int) date_diff( $date_from, $date_to )->days;
		} catch ( Exception $e ) {
			$days = 0;
		}

		// We need to show date in month format.
		if ( $days >= 364 ) {
			$this->period_dimension = 'yearMonth';
		} elseif ( $days >= 89 ) {
			$this->period_dimension = 'yearWeek';
		} elseif ( $days > 0 ) {
			$this->period_dimension = 'date';
		} else {
			$this->period_dimension = 'dateHour';
		}

		// Create the DateRange object.
		$date = new DateRange();
		// Set start date.
		$date->setStartDate( $from );
		// Set end date.
		$date->setEndDate( $to );

		return $date;
	}

	/**
	 * Setup reporting period to get stats data.
	 *
	 * Only allowed periods will be processed. For other periods you
	 * can use custom type and pass the from and to dates.
	 *
	 * @since 3.2.0
	 *
	 * @param string $from Start date.
	 * @param string $to   End date.
	 *
	 * @return DateRange
	 */
	public function get_previous_period( $from, $to ) {
		$date = false;

		// Get previous period.
		$period = Helper::get_previous_period( $from, $to );

		// Create the DateRange object.
		if ( ! empty( $period['from'] ) ) {
			$date = new DateRange();
			// Set start date.
			$date->setStartDate( $period['from'] );
			// Set end date.
			$date->setEndDate( $period['to'] );
		}

		return $date;
	}

	/**
	 * Set reports dimensions for API request.
	 *
	 * You can query multiple dimensions by passing metric name as an array.
	 * Do not append ga: prefix, it will be handled within the method.
	 *
	 * @since 3.2.0
	 *
	 * @param DateRange[]             $periods           Date range periods.
	 * @param Metric[]                $metrics           Metrics array.
	 * @param Dimension[]             $dimensions        Dimensions array.
	 * @param OrderBy[]               $orders            Sorting order array.
	 * @param DimensionFilterClause[] $dimention_filters Dimension filters.
	 * @param MetricFilterClause[]    $metrics_filters   Metric filters (currently not used).
	 * @param int                     $page_size         Maximum no. of items.
	 *
	 * @return ReportRequest
	 */
	public function get_request( $periods, $metrics, $dimensions = array(), $orders = array(), $dimention_filters = array(), $metrics_filters = array(), $page_size = 0 ) {
		// Create the ReportRequest object.
		$request = new ReportRequest();
		// Set view.
		$request->setViewId( $this->account );
		// Set date range.
		$request->setDateRanges( $periods );
		// Set metrics.
		$request->setMetrics( $metrics );
		// Set dimensions.
		$request->setDimensions( $dimensions );
		// Maximum no. of items.
		if ( ! empty( $page_size ) ) {
			$request->setPageSize( $page_size );
		}
		// Set sampling level.
		$request->setSamplingLevel( 'LARGE' );
		// Set sorting.
		if ( ! empty( $orders ) ) {
			$request->setOrderBys( $orders );
		}

		// Get url filters.
		$basic_filters = $this->get_basic_filters();

		// Include basic filters.
		if ( ! empty( $basic_filters ) ) {
			$dimention_filters = array_merge( $basic_filters, $dimention_filters );
		}

		// Set dimension filters.
		if ( ! empty( $dimention_filters ) ) {
			$request->setDimensionFilterClauses( $dimention_filters );
		}
		// Set metric filters.
		if ( ! empty( $metrics_filters ) ) {
			$request->setMetricFilterClauses( $metrics_filters );
		}
		// Do not exclude empty rows.
		$request->setIncludeEmptyRows( true );

		// Hide unwanted data.
		$request->setHideTotals( true );
		$request->setHideValueRanges( true );

		return $request;
	}

	/**
	 * Set basic filters require for all requests.
	 *
	 * Few filters that are required to make sure only the required
	 * data is being displayed.
	 *
	 * @since 3.2.0
	 */
	protected function get_basic_filters() {
		$filters = array();

		// Remove unwanted items.
		// $filters[] = $this->get_dimension_filter( [
		// 'pagePath' => [
		// 'value' => '!@preview=true',
		// ],
		// ] );
		// When subsite data is loaded from network credentials,
		// make sure to show stats only for current site.
		if ( ! $this->network && Helper::instance()->login_source( $this->network ) === 'network' ) {
			$filters[] = $this->get_url_filter();
		}

		return $filters;
	}

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
	protected function get_url_filter() {
		$filters = array();

		// No need for network admin stats.
		if ( $this->is_network() ) {
			return array();
		}

		// Get home url.
		$url = home_url();

		/**
		 * Filter hook to alter the home url before filtering.
		 *
		 * Domain mapping plugins can use this filter to add the support.
		 *
		 * @since 3.2.4
		 *
		 * @param string $url Home URL.
		 */
		$url = apply_filters( 'beehive_google_analytics_request_home_url', $url );

		// Remove the protocols.
		$url_parts = explode( '/', str_replace( array( 'http://', 'https://' ), '', $url ) );

		// In case it is empty, try site url.
		if ( ! $url_parts ) {
			$url_parts = explode( '/', str_replace( array( 'http://', 'https://' ), '', site_url() ) );
		}

		// Set host filter.
		$filters[] = $this->get_dimension_filter(
			array(
				'hostname' => array(
					'value'    => $url_parts[0],
					'operator' => 'EXACT',
				),
			)
		);

		// If its in subdirectory mode, then set correct beginning for page path.
		if ( count( $url_parts ) > 1 ) {
			unset( $url_parts[0] );
			$pagepath = implode( '/', $url_parts );

			// Set path filter.
			$filters[] = $this->get_dimension_filter(
				array(
					'pagePath' => array(
						'value' => "^/$pagepath/.*",
					),
				)
			);
		}

		return $filters;
	}
}
