<?php
/**
 * The Google API request setup class.
 *
 * @link    http://wpmudev.com
 * @since   3.4.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Requests
 */

namespace Beehive\Core\Modules\Google_Analytics\Stats\Requests;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Exception;
use Beehive\Google\Service\AnalyticsData;
use Beehive\Core\Modules\Google_Auth\Auth;
use Beehive\Core\Modules\Google_Analytics\Data;
use Beehive\Google\Service\AnalyticsData\Filter;
use Beehive\Google\Service\AnalyticsData\Metric;
use Beehive\Core\Modules\Google_Analytics\Helper;
use Beehive\Google\Service\AnalyticsData\OrderBy;
use Beehive\Google\Service\AnalyticsData\Dimension;
use Beehive\Google\Service\AnalyticsData\DateRange;
use Beehive\Google\Service\AnalyticsData\StringFilter;
use Beehive\Google\Service\AnalyticsData\MetricOrderBy;
use Beehive\Google\Service\Exception as Google_Exception;
use Beehive\Google\Service\AnalyticsData\FilterExpression;
use Beehive\Google\Service\AnalyticsData\DimensionOrderBy;
use Beehive\Google\Service\AnalyticsData\RunReportRequest;
use Beehive\Core\Modules\Google_Analytics\Stats\Processors;
use Beehive\Google\Service\AnalyticsData\FilterExpressionList;
use Beehive\Google\Service\AnalyticsData\RunRealtimeReportRequest;
use Beehive\Google\Service\AnalyticsData\RunRealtimeReportResponse;

/**
 * Class Request
 *
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Requests
 */
class GA4 extends Request {

	/**
	 * Set API request for the realtime stats.
	 *
	 * @since 3.4.0
	 *
	 * @param bool            $network   Network flag.
	 * @param \Exception|bool $exception Exception if any.
	 *
	 * @return RunRealtimeReportResponse|false
	 */
	public function realtime( $network = false, &$exception = false ) {
		// Setup account.
		$this->setup_account( $network );

		// Setup login.
		Processors\GA4::instance()->setup( $network );

		try {
			// Analytics v4 instance.
			$analytics = new AnalyticsData( Auth::instance()->client() );
			// Request instance.
			$request = new RunRealtimeReportRequest();
			// Setup metrics.
			$request->setMetrics( $this->get_metrics( array( 'activeUsers' ) ) );
			// Setup dimensions.
			$request->setDimensions( $this->get_dimensions( array( 'deviceCategory' ) ) );
			// Setup aggregations.
			$request->setMetricAggregations( 'TOTAL' );

			// Run request and get response.
			$stats = $analytics->properties->runRealtimeReport( $this->account, $request );
		} catch ( Google_Exception $e ) {
			$stats     = false;
			$exception = $e;
		} catch ( Exception $e ) {
			$stats     = false;
			$exception = $e;
		}

		return $stats;
	}

	/**
	 * Set API request for popular pages widget stats.
	 *
	 * This is not required in network admin.
	 *
	 * @since 3.4.0
	 *
	 * @param int $page_size Page size.
	 *
	 * @return RunReportRequest
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
		 * @param int  $page_size Page size (default is 0 for all).
		 * @param bool $network   Network flag.
		 */
		$page_size = apply_filters( 'beehive_google_analytics_request_popular_posts_size', $page_size, $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'screenPageViews' ) );

		$dimensions = $this->get_dimensions( array( 'hostname', 'pageTitle', 'pagePathPlusQueryString' ) );

		// Order by pageviews.
		$orders = $this->get_orders( array( 'screenPageViews' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, array(), $page_size );
	}

	/**
	 * Set API requests for post meta box.
	 *
	 * @since 3.4.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $from    Start date.
	 * @param string $to      End date.
	 *
	 * @return RunReportRequest[]
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
			$dimension_filters[] = $this->get_dimension_filter( array( 'pagePath' => $url ) );

			// Set summary request.
			$metrics = $this->get_metrics(
				array(
					'sessions',
					'screenPageViews',
					'totalUsers',
					'screenPageViewsPerSession',
					'averageSessionDuration',
					'bounceRate',
				),
				'summary'
			);

			$requests['multiple'] = array(
				'summary' => $this->get_request( $periods, $metrics, array(), array(), $dimension_filters ),
			);
		}

		return $requests;
	}

	/**
	 * Set API request for summary stats.
	 *
	 * Summary stats request is using 2 date ranges.
	 *
	 * @since 3.4.0
	 *
	 * @return RunReportRequest
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
				'screenPageViews',
				'totalUsers',
				'screenPageViewsPerSession',
				'averageSessionDuration',
				'bounceRate',
				'newUsers',
			),
			'summary'
		);

		return $this->get_request( $periods, $metrics );
	}

	/**
	 * Set API request for top visited pages stats.
	 *
	 * @since 3.4.0
	 *
	 * @param int $page_size Page size.
	 *
	 * @return RunReportRequest
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
		$metrics = $this->get_metrics( array( 'averageSessionDuration', 'screenPageViews' ) );

		$dimensions = $this->get_dimensions( array( 'hostname', 'pageTitle', 'pagePath' ) );

		// Order by pageviews.
		$orders = $this->get_orders( array( 'screenPageViews' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, array(), array(), $page_size );
	}

	/**
	 * Set API request for the top visited countries.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $current   Is this request is for current period.
	 * @param int  $page_size Page size.
	 *
	 * @return RunReportRequest
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
		$metrics = $this->get_metrics( array( 'screenPageViews' ) );

		$dimensions = $this->get_dimensions( array( 'country', 'countryId' ) );

		// Order by pageviews.
		$orders = $this->get_orders( array( 'screenPageViews' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, array(), array(), $page_size );
	}

	/**
	 * Set API request for the mediums stats.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $current   Is this request is for current period.
	 * @param int  $page_size Page size.
	 *
	 * @return RunReportRequest
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
		$metrics = $this->get_metrics( array( 'sessions' ) );

		$dimensions = $this->get_dimensions( array( 'sessionDefaultChannelGrouping' ) );

		// Order by sessions.
		$orders = $this->get_orders( array( 'sessions' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, array(), $page_size );
	}

	/**
	 * Set API request for the social network stats.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return RunReportRequest
	 */
	public function social_networks( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'sessions' ) );

		$dimensions = $this->get_dimensions( array( 'sourceMedium' ) );

		// Filter only social channels.
		$dimension_filter = $this->get_dimension_filter( array( 'sessionDefaultChannelGrouping' => 'Social' ) );

		// Order by sessions.
		$orders = $this->get_orders( array( 'sessions' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, $dimension_filter );
	}

	/**
	 * Set API request for sessions stats.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return RunReportRequest
	 */
	public function sessions( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'sessions' ) );

		$period_dimension = $this->get_period_dimension();

		$dimensions = $this->get_dimensions( array( $period_dimension ) );

		return $this->get_request( $periods, $metrics, $dimensions );
	}

	/**
	 * Set API request for the search engine traffic stats.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $current   Is this request is for current period.
	 * @param int  $page_size Page size.
	 *
	 * @return RunReportRequest
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
		$metrics = $this->get_metrics( array( 'sessions' ) );

		$dimensions = $this->get_dimensions( array( 'sessionMedium', 'sessionSource' ) );

		$dimension_filter = $this->get_dimension_filter( array( 'sessionMedium' => 'organic' ) );

		// Order by sessions.
		$orders = $this->get_orders( array( 'sessions' ) );

		return $this->get_request( $periods, $metrics, $dimensions, $orders, $dimension_filter, $page_size );
	}

	/**
	 * Set API request for the users list stats.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $current Is this request is for current period.
	 *
	 * @return RunReportRequest
	 */
	public function users( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'totalUsers' ) );

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
	 * @return RunReportRequest
	 */
	public function pageviews( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'screenPageViews' ) );

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
	 * @return RunReportRequest
	 */
	public function page_sessions( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'screenPageViewsPerSession' ) );

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
	 * @return RunReportRequest
	 */
	public function average_sessions( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'averageSessionDuration' ) );

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
	 * @return RunReportRequest
	 */
	public function bounce_rates( $current = true ) {
		// Setup dates.
		$periods = array( $current ? $this->current_period : $this->previous_period );

		// Setup account.
		$this->setup_account( $this->network );

		// Set top pages request.
		$metrics = $this->get_metrics( array( 'bounceRate' ) );

		$period_dimension = $this->get_period_dimension();

		$dimensions = $this->get_dimensions( array( $period_dimension ) );

		return $this->get_request( $periods, $metrics, $dimensions );
	}

	/**
	 * Set reports dimensions for API request.
	 *
	 * You can query multiple dimensions by passing metric name as an array.
	 * Do not append ga: prefix, it will be handled within the method.
	 *
	 * @since 3.2.0
	 *
	 * @param DateRange[]        $periods            Date range periods.
	 * @param Metric[]           $metrics            Metrics array.
	 * @param Dimension[]        $dimensions         Dimensions array.
	 * @param OrderBy[]          $orders             Sorting order array.
	 * @param FilterExpression[] $filter_expressions Dimension filter expressions.
	 * @param int                $page_size          Maximum no. of items.
	 *
	 * @return RunReportRequest
	 */
	public function get_request( $periods, $metrics, $dimensions = array(), $orders = array(), $filter_expressions = null, $page_size = 0 ) {
		// Create the ReportRequest object.
		$request = new RunReportRequest();
		// Set view.
		$request->setProperty( $this->account );
		// Set date range.
		$request->setDateRanges( $periods );
		// Set metrics.
		$request->setMetrics( $metrics );
		// Set dimensions.
		$request->setDimensions( $dimensions );
		// Maximum no. of items.
		if ( ! empty( $page_size ) ) {
			$request->setLimit( $page_size );
		}
		// Set sorting if required.
		if ( ! empty( $orders ) ) {
			$request->setOrderBys( $orders );
		} else {
			$orders = $this->get_order_from_dimensions( $dimensions );
			if ( ! empty( $orders ) ) {
				$request->setOrderBys( $orders );
			}
		}

		// Get url filters.
		$basic_filters = $this->get_basic_filters();

		// Include basic filters.
		if ( ! empty( $basic_filters ) ) {
			$filter_expressions = is_array( $filter_expressions ) ? array_merge( $basic_filters, $filter_expressions ) : $basic_filters;
		}

		// Set dimension filters.
		if ( ! empty( $filter_expressions ) ) {
			$dimension_filter = $this->get_dimension_filter_expression( $filter_expressions );
			// Only if proper instance.
			if ( $dimension_filter instanceof FilterExpression ) {
				$request->setDimensionFilter( $dimension_filter );
			}
		}

		// Do not exclude empty rows.
		$request->setKeepEmptyRows( true );

		return $request;
	}

	/**
	 * Get sort order from dimensions.
	 *
	 * @param Dimension[] $dimensions Dimensions array.
	 *
	 * @return array
	 */
	private function get_order_from_dimensions( $dimensions = array() ) {
		$orders = array();

		// If dimensions are not empty.
		if ( ! empty( $dimensions[0] ) && $dimensions[0] instanceof Dimension ) {
			// Sort based on first dimension.
			$orders = $this->get_orders( array( $dimensions[0]->getName() ), 'dimension' );
		}

		return $orders;
	}

	/**
	 * Get reports metrics for API request.
	 *
	 * You can query multiple metrics by passing metric name as an array.
	 * https://ga-dev-tools.web.app/ga4/dimensions-metrics-explorer/
	 *
	 * @since 3.4.0
	 *
	 * @param array  $metrics Metric types.
	 * @param string $alias   NOT USED for GA4.
	 *
	 * @return Metric[]
	 */
	public function get_metrics( $metrics = array(), $alias = '' ) {
		// Empty the metrics.
		$metrics_instances = array();

		// Set each metrics.
		foreach ( (array) $metrics as $name ) {
			// Create the Metrics object.
			$metric = new Metric();
			// Set metrics name.
			$metric->setName( $name );

			// Set to metrics instances.
			$metrics_instances[] = $metric;
		}

		return $metrics_instances;
	}

	/**
	 * Set reports dimensions for API request.
	 *
	 * You can query multiple dimensions by passing metric name as an array.
	 * https://ga-dev-tools.web.app/ga4/dimensions-metrics-explorer/
	 *
	 * @since 3.4.0
	 *
	 * @param array $dimensions Dimension types.
	 *
	 * @return Dimension[]
	 */
	public function get_dimensions( $dimensions = array() ) {
		// Empty the dimensions.
		$dimension_instances = array();

		// Set each metrics.
		foreach ( (array) $dimensions as $name ) {
			// Create the Metrics object.
			$dimension = new Dimension();
			// Set dimension type.
			$dimension->setName( $name );

			// Set to dimension instances.
			$dimension_instances[] = $dimension;
		}

		return $dimension_instances;
	}

	/**
	 * Set reports dimensions filter.
	 *
	 * Do not append ga: to field names.
	 *
	 * @since 3.4.0
	 *
	 * @param array  $filter_params Filter items.
	 * @param string $operator      Match type (ignore the variable name).
	 *
	 * @return array
	 */
	public function get_dimension_filter( $filter_params, $operator = '' ) {
		// Create filter clause object.
		$expressions = array();

		// Set each fields.
		foreach ( $filter_params as $field => $value ) {
			// Setup value.
			$string_filter = new StringFilter();
			$string_filter->setValue( $value );
			// Ignore the variable name.
			if ( ! empty( $operator ) ) {
				$string_filter->setMatchType( $operator );
			}

			// Setup field.
			$filter = new Filter();
			$filter->setFieldName( $field );
			$filter->setStringFilter( $string_filter );

			// Setup filter expression.
			$expression = new FilterExpression();
			$expression->setFilter( $filter );

			$expressions[] = $expression;
		}

		return $expressions;
	}

	/**
	 * Set reports dimensions filters.
	 *
	 * @since 3.4.0
	 *
	 * @param FilterExpression[] $expressions Filter items.
	 *
	 * @return FilterExpression
	 */
	public function get_dimension_filter_expression( $expressions ) {
		$expression_list = new FilterExpressionList();
		$expression_list->setExpressions( $expressions );

		$expression = new FilterExpression();

		if ( count( $expressions ) > 1 ) {
			$expression->setAndGroup( $expression_list );
		} else {
			$expression = $expressions[0];
		}

		return $expression;
	}

	/**
	 * Set reports sorting to filter results.
	 *
	 * You can sort using multiple fields. To get list of items see
	 * https://ga-dev-tools.web.app/ga4/dimensions-metrics-explorer/
	 *
	 * @since 3.4.0
	 *
	 * @param array $fields Fields to sort based on.
	 * @param array $type   Type (metric or dimension).
	 *
	 * @return OrderBy[]|array
	 */
	public function get_orders( $fields = array(), $type = 'metric' ) {
		// Empty the sorting.
		$orders = array();

		// Only when fields are not empty.
		if ( ! empty( $fields ) ) {
			// Set each metrics.
			foreach ( (array) $fields as $field ) {
				$order_by = new OrderBy();
				if ( 'metric' === $type ) {
					$metric_order_by = new MetricOrderBy();
					$metric_order_by->setMetricName( $field );
					$order_by->setMetric( $metric_order_by );
					$order_by->setDesc( true );
				} else {
					$dimension_order_by = new DimensionOrderBy();
					$dimension_order_by->setDimensionName( $field );
					// $dimension_order_by->setOrderType( 'NUMERIC' );
					$order_by->setDimension( $dimension_order_by );
					$order_by->setDesc( false );
				}

				// Set to sorting instances.
				$orders[] = $order_by;
			}
		}

		return $orders;
	}

	/**
	 * Setup reporting period to get stats data.
	 *
	 * Only allowed periods will be processed. For other periods you
	 * can use custom type and pass the from and to dates.
	 *
	 * @since 3.4.0
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
			$this->period_dimension = 'month';
		} elseif ( $days >= 89 ) {
			$this->period_dimension = 'week';
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
	 * @since 3.4.0
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
	 * Set basic filters require for all requests.
	 *
	 * Few filters that are required to make sure only the required
	 * data is being displayed.
	 *
	 * @since 3.4.0
	 *
	 * @return array
	 */
	protected function get_basic_filters() {
		$filters = array();

		// When subsite data is loaded from network credentials,
		// make sure to show stats only for current site.
		if ( ! $this->network && Helper::instance()->login_source( $this->network ) === 'network' ) {
			$filters = $this->get_url_filter();
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
	 * @since 3.4.0
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

		// Get host filter.
		$host_filter = $this->get_dimension_filter( array( 'hostname' => $url_parts[0] ) );

		// Set host filter.
		if ( ! empty( $host_filter ) ) {
			$filters = array_merge( $host_filter, $filters );
		}

		// If its in subdirectory mode, then set correct beginning for page path.
		if ( count( $url_parts ) > 1 ) {
			unset( $url_parts[0] );
			$page_path = implode( '/', $url_parts );

			// Get page path filter.
			$page_path_filter = $this->get_dimension_filter( array( 'pagePath' => "^/$page_path/.*" ), 'FULL_REGEXP' );

			// Set path filter.
			if ( ! empty( $page_path_filter ) ) {
				$filters = array_merge( $page_path_filter, $filters );
			}
		}

		return $filters;
	}

	/**
	 * Setup GA account string for the reports data.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $network Network flag.
	 *
	 * @return void
	 */
	public function setup_account( $network = false ) {
		// Decide login source.
		$network = Helper::instance()->login_source( $network ) === 'network';

		// Get currently assigned id.
		$stream = beehive_analytics()->settings->get( 'stream', 'google', $network );

		// Some times, stream can be empty when user didn't save the settings. We need to handle this ourselves.
		if ( empty( $stream ) ) {
			// Get available profiles.
			$streams = Data::instance()->streams( $network );
			if ( ! empty( $streams[0]['property'] ) ) {
				// Set the first item for now.
				$stream = $streams[0]['property'];
				// Update the settings.
				beehive_analytics()->settings->update( 'stream', $stream, 'google', $network );
			}
		}

		// Set stream instead of account.
		$this->set_property_from_stream( $stream, $network );
	}

	/**
	 * Set the property ID from the stream string.
	 *
	 * @since 3.4.0
	 *
	 * @param string $stream  Stream string.
	 * @param bool   $network Network flag.
	 *
	 * @return void
	 */
	private function set_property_from_stream( $stream, $network ) {
		// We need a stream.
		if ( empty( $stream ) ) {
			return;
		}

		// Get available profiles.
		$streams = Data::instance()->streams( $network );

		if ( isset( $streams[ $stream ]['property'] ) ) {
			$this->account = $streams[ $stream ]['property'];
		}
	}
}
