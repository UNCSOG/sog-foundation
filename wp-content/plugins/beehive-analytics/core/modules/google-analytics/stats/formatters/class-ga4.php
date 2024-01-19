<?php
/**
 * The data formatter class for GA4 stats.
 *
 * This class is little complex. Google Analytics Reporting API
 * response is really complex. We need to properly format the response.
 * Modify with caution.
 *
 * @link    http://wpmudev.com
 * @since   3.4.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics\Stats
 */

namespace Beehive\Core\Modules\Google_Analytics\Stats\Formatters;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Google\Service\AnalyticsData\RunReportResponse;
use Beehive\Google\Service\AnalyticsData\RunRealtimeReportResponse;

/**
 * Class Format
 *
 * @package Beehive\Core\Modules\Google_Analytics\Stats
 */
class GA4 extends Formatter {

	/**
	 * Format the API request response data.
	 *
	 * We are getting all the stats data in a single or a few API request(s).
	 * So, we need to format the request response and get individual
	 * data based on the response.
	 *
	 * @since 3.4.0
	 *
	 * @param array  $data       Response.
	 * @param string $stats_type Stats type.
	 * @param array  $requests   Requests.
	 *
	 * @return array
	 */
	public function format( $data, $stats_type = 'stats', $requests = array() ) {
		$stats = array();

		foreach ( $data as $type => $reports ) {
			$stats[ $type ] = $this->setup( $reports, $requests[ $type ] );
		}

		// Format the data for different usage.
		switch ( $stats_type ) {
			case 'stats':
				$stats = $this->stats_page( $stats );
				break;
			case 'dashboard':
				$stats = $this->dashboard_widget( $stats );
				break;
			case 'popular_widget':
				$stats = $this->popular_widget( $stats );
				break;
			case 'post':
				$stats = $this->post( $stats );
				break;
			case 'summary':
				$stats = $this->dashboard_summary( $stats );
				break;
			default:
				$stats = array();
				break;
		}

		return $stats;
	}

	/**
	 * Format the response data to readable format.
	 *
	 * Get the metrics and dimension values into an array.
	 *
	 * @since 3.4.0
	 *
	 * @param RunReportResponse[] $reports  Stats data from API.
	 * @param array               $requests Requests.
	 *
	 * @return array
	 */
	private function setup( $reports, $requests ) {
		$index = 0;
		$stats = array();

		foreach ( $requests as $type => $request ) {
			// Check if a corresponding response found.
			if ( ! isset( $reports[ $index ] ) ) {
				$index ++;
				continue;
			}

			$report_stats = array();
			$report       = $reports[ $index ];

			$metric_headers         = $report->getMetricHeaders();
			$dimension_headers      = $report->getDimensionHeaders();
			$metric_headers_count   = count( $metric_headers );
			$dimension_header_count = count( $dimension_headers );
			$rows                   = $report->getRows();

			if ( is_array( $rows ) ) {
				foreach ( $report->getRows() as $row ) {
					$values           = array();
					$metric_values    = $row->getMetricValues();
					$dimension_values = $row->getDimensionValues();

					// Get dimension data.
					for ( $i = 0; $i < $dimension_header_count; $i ++ ) {
						$name = $dimension_headers[ $i ]->getName();

						if ( ! empty( $dimension_values[ $i ] ) ) {
							$values['dimensions'][ $name ] = $this->format_value( $name, $dimension_values[ $i ]->getValue() );
						}
					}

					// Get metrics data.
					for ( $i = 0; $i < $metric_headers_count; $i ++ ) {
						$name = $metric_headers[ $i ]->getName();

						if ( ! empty( $metric_values[ $i ] ) ) {
							$values['metrics'][ $name ] = $this->format_value( $name, $metric_values[ $i ]->getValue() );
						}
					}

					$report_stats[] = $values;
				}
			}

			// Set report data for type.
			$stats[ $type ] = $report_stats;

			$index ++;
		}

		return $stats;
	}

	/**
	 * Format the API request response data for realtime stats.
	 *
	 * We are getting all the stats data in a weired format.
	 *
	 * @since 3.4.0
	 *
	 * @param RunRealtimeReportResponse $reports Report data.
	 *
	 * @return array
	 */
	public function format_realtime( $reports ) {
		// Default stats.
		$stats = array(
			'total'        => 0,
			'total_simple' => 0,
			'devices'      => array(
				'desktop' => array(
					'device' => __( 'Desktop', 'ga_trans' ),
					'users'  => 0,
				),
				'mobile'  => array(
					'device' => __( 'Mobile', 'ga_trans' ),
					'users'  => 0,
				),
				'tablet'  => array(
					'device' => __( 'Tablet', 'ga_trans' ),
					'users'  => 0,
				),
			),
		);

		if ( ! empty( $reports ) ) {
			// Report data.
			$rows = $reports->getRows();
			// Total values.
			$totals = $reports->getTotals();

			// Set total value.
			if ( isset( $totals[0]->getMetricValues()[0] ) ) {
				$total                 = $totals[0]->getMetricValues()[0]->getValue();
				$stats['total']        = (int) $total;
				$stats['total_simple'] = empty( $total ) ? 0 : $this->number_to_simple_text( $total );
			}

			if ( ! empty( $rows ) ) {
				foreach ( $rows as $row ) {
					$dimensions = $row->getDimensionValues();
					$metrics    = $row->getMetricValues();
					$count      = count( $dimensions );

					// Continue only if matching dimensions and metrics found.
					if ( $count <= 0 || count( $metrics ) !== $count ) {
						continue;
					}

					for ( $i = 0; $i < $count; $i ++ ) {
						if ( isset( $dimensions[ $i ], $metrics[ $i ] ) ) {
							// Get the device name.
							$name = strtolower( $dimensions[ $i ]->getValue() );
							// Set stats.
							$stats['devices'][ $name ] = array(
								'device' => ucwords( $name ),
								'users'  => (int) $metrics[ $i ]->getValue(),
							);
						}
					}
				}
			}
		}

		return $stats;
	}

	/**
	 * Get the proper period data value.
	 *
	 * Period dimension keys can be different based on
	 * the period selected by the user. Check all possible
	 * keys and return the value.
	 *
	 * @since 3.4.0
	 *
	 * @param array $data Data.
	 *
	 * @return string
	 */
	protected function get_period_value( $data ) {
		// Get the dimension type.
		if ( isset( $data['dimensions']['month'] ) ) {
			return $data['dimensions']['month'];
		} elseif ( isset( $data['dimensions']['week'] ) ) {
			return $data['dimensions']['week'];
		} elseif ( isset( $data['dimensions']['dateHour'] ) ) {
			return $data['dimensions']['dateHour'];
		} elseif ( isset( $data['dimensions']['date'] ) ) {
			return $data['dimensions']['date'];
		}

		return '';
	}

	/**
	 * Format the data for dashboard widget.
	 *
	 * Format the data array into the format of dashboard widget.
	 *
	 * @since 3.4.0
	 *
	 * @param array $data Stats data from Google.
	 *
	 * @return array $stats
	 */
	private function dashboard_widget( $data ) {
		$stats = array();

		// Oi, we need data.
		if ( empty( $data ) ) {
			return $stats;
		}

		// Format summary data.
		if ( isset( $data['current'], $data['multiple'] ) ) {
			$stats['summary'] = isset( $data['multiple']['summary'] ) ? $this->summary( $data['multiple']['summary'] ) : array();

			// Format pages list.
			// @todo Avoid double foreach loop.
			if ( isset( $data['multiple']['top_pages'] ) ) {
				$page_count = 0;
				$pages      = array();

				foreach ( $data['multiple']['top_pages'] as $page ) {
					if ( ! isset( $pages[ $page['dimensions']['pagePath'] ] ) ) {
						$pages[ $page['dimensions']['pagePath'] ] = array();
					}

					if ( isset( $page['dimensions']['dateRange'] ) && 'date_range_0' === $page['dimensions']['dateRange'] ) {
						// Top page full details.
						$pages[ $page['dimensions']['pagePath'] ]['link']    = $this->get_anchor( $page['dimensions']['hostname'], $page['dimensions']['pagePath'], $page['dimensions']['pageTitle'] );
						$pages[ $page['dimensions']['pagePath'] ]['session'] = $this->get_time( $page['metrics']['averageSessionDuration'], 'string' );
						$pages[ $page['dimensions']['pagePath'] ]['current'] = $page['metrics']['screenPageViews'];

						// Add top page to summary.
						if ( 0 === $page_count ) {
							$stats['summary']['page'] = array(
								'value'     => $page['dimensions']['pageTitle'],
								'html'      => $this->get_anchor( $page['dimensions']['hostname'], $page['dimensions']['pagePath'], $page['dimensions']['pageTitle'], true ),
								'pageviews' => $page['metrics']['screenPageViews'],
							);
						}

						$page_count ++;
					} elseif ( isset( $page['dimensions']['dateRange'] ) && 'date_range_1' === $page['dimensions']['dateRange'] ) {
						$pages[ $page['dimensions']['pagePath'] ]['previous'] = $page['metrics']['screenPageViews'];
					}
				}

				// @todo Avoid double foreach loop.
				foreach ( $pages as $page ) {
					// Top countries full details.
					if ( isset( $page['link'] ) ) {
						$stats['pages'][] = array(
							$page['link'],
							isset( $page['session'] ) ? $page['session'] : 0,
							isset( $page['current'] ) ? $page['current'] : 0,
							$this->trend_value(
								'screenPageViews',
								isset( $page['current'] ) ? $page['current'] : 0, // Current period value.
								isset( $page['previous'] ) ? $page['previous'] : 0 // Previous period value.
							),
						);
					}
				}
			}

			// Format countries list.
			if ( isset( $data['current']['top_countries'] ) ) {
				$country_count = 0;

				foreach ( $data['current']['top_countries'] as $country ) {
					// Top countries full details.
					$stats['countries'][] = array(
						$country['dimensions']['country'],
						$country['dimensions']['countryId'],
						$country['metrics']['screenPageViews'],
					);

					// Add top country to summary.
					if ( 0 === $country_count ) {
						$stats['summary']['country'] = array(
							'value'     => $country['dimensions']['country'],
							'code'      => $country['dimensions']['countryId'],
							'pageviews' => $country['metrics']['screenPageViews'],
						);
					}

					$country_count ++;
				}
			}

			// Format sources and mediums list.
			if ( isset( $data['current']['mediums'] ) ) {
				$medium_count = 0;

				foreach ( $data['current']['mediums'] as $medium ) {
					// Medium data.
					$stats['mediums'][] = array( $medium['dimensions']['sessionDefaultChannelGrouping'], $medium['metrics']['sessions'] );

					// Add top medium to summary.
					if ( 0 === $medium_count ) {
						$stats['summary']['medium'] = array(
							'value'    => $medium['dimensions']['sessionDefaultChannelGrouping'],
							'sessions' => $medium['metrics']['sessions'],
						);
					}

					$medium_count ++;
				}
			}

			// Format sources and mediums list.
			if ( isset( $data['current']['search_engines'] ) ) {
				$search_engine_count = 0;

				foreach ( $data['current']['search_engines'] as $search_engine ) {
					// Search engine data.
					$stats['search_engines'][] = array(
						ucfirst( $search_engine['dimensions']['sessionSource'] ),
						$search_engine['metrics']['sessions'],
					);

					// Add top search engine to summary.
					if ( 0 === $search_engine_count ) {
						$stats['summary']['search_engine'] = array(
							'value'    => ucfirst( $search_engine['dimensions']['sessionSource'] ),
							'sessions' => $search_engine['metrics']['sessions'],
						);
					}

					$search_engine_count ++;
				}
			}

			// Format sources and mediums list.
			if ( isset( $data['current']['social_networks'] ) ) {
				$social_network_count = 0;

				foreach ( $data['current']['social_networks'] as $social_network ) {
					// Social network data.
					$stats['social_networks'][] = array( $social_network['dimensions']['sourceMedium'], $social_network['metrics']['sessions'] );

					// Add top social network to summary.
					if ( 0 === $social_network_count ) {
						$stats['summary']['social_network'] = array(
							'value'    => $social_network['dimensions']['sourceMedium'],
							'sessions' => $social_network['metrics']['sessions'],
						);
					}

					$social_network_count ++;
				}
			}

			// Format sessions list.
			if ( isset( $data['current']['sessions'] ) ) {
				foreach ( $data['current']['sessions'] as $session ) {
					$date                = $this->get_period_value( $session );
					$stats['sessions'][] = array( $date, $session['metrics']['sessions'] );
				}
			}

			// Format users list.
			if ( isset( $data['current']['users'] ) ) {
				foreach ( $data['current']['users'] as $user ) {
					$date             = $this->get_period_value( $user );
					$stats['users'][] = array( $date, $user['metrics']['totalUsers'] );
				}
			}

			// Format page views list.
			if ( isset( $data['current']['pageviews'] ) ) {
				foreach ( $data['current']['pageviews'] as $pageview ) {
					$date                 = $this->get_period_value( $pageview );
					$stats['pageviews'][] = array( $date, $pageview['metrics']['screenPageViews'] );
				}
			}

			// Format pages per sessions list.
			if ( isset( $data['current']['page_sessions'] ) ) {
				foreach ( $data['current']['page_sessions'] as $session ) {
					$date                     = $this->get_period_value( $session );
					$stats['page_sessions'][] = array( $date, $session['metrics']['screenPageViewsPerSession'] );
				}
			}

			// Format average sessions list.
			if ( isset( $data['current']['average_sessions'] ) ) {
				foreach ( $data['current']['average_sessions'] as $session ) {
					$date                        = $this->get_period_value( $session );
					$stats['average_sessions'][] = array( $date, $session['metrics']['averageSessionDuration'] );
				}
			}

			// Format bounce rates list.
			if ( isset( $data['current']['bounce_rates'] ) ) {
				foreach ( $data['current']['bounce_rates'] as $rate ) {
					$date                    = $this->get_period_value( $rate );
					$stats['bounce_rates'][] = array( $date, $rate['metrics']['bounceRate'] );
				}
			}
		}

		return $stats;
	}

	/**
	 * Format the data for front end widget.
	 *
	 * Get the required data from Google response and format.
	 *
	 * @since 3.4.0
	 *
	 * @param array $data Stats data from Google.
	 *
	 * @return array $stats
	 */
	private function popular_widget( $data ) {
		$stats = array();

		// Format pages list.
		if ( ! empty( $data['current']['popular_pages'] ) ) {
			foreach ( $data['current']['popular_pages'] as $row ) {
				if ( isset( $row['dimensions']['hostname'], $row['dimensions']['pagePathPlusQueryString'] ) ) {
					// Top pages list.
					$stats[] = $this->get_link( $row['dimensions']['hostname'], $row['dimensions']['pagePathPlusQueryString'] );
				}
			}
		}

		return $stats;
	}

	/**
	 * Format the data for all stats page.
	 *
	 * Get the required data from Google response and format.
	 *
	 * @since 3.4.0
	 *
	 * @param array $data Stats data from Google.
	 *
	 * @return array $stats
	 */
	private function post( $data ) {
		// Oy hello, we need data.
		if ( empty( $data['multiple'] ) ) {
			return array();
		}

		// Format summary data.
		return $this->summary( $data['multiple']['summary'] );
	}

	/**
	 * Format the data for the dashboard summary page.
	 *
	 * Format the data array into the format of dashboard widget.
	 *
	 * @since 3.4.0
	 *
	 * @param array $data Stats data from Google.
	 *
	 * @return array $stats
	 */
	private function dashboard_summary( $data ) {
		$stats = array();

		// Oi, we need data.
		if ( empty( $data ) ) {
			return $stats;
		}

		// Format summary data.
		if ( isset( $data['multiple'], $data['current'] ) ) {
			$stats = array(
				// Setup summary.
				'summary'       => isset( $data['multiple']['summary'] ) ? $this->summary( $data['multiple']['summary'] ) : array(),
				'medium'        => array(),
				'search_engine' => array(),
				'page'          => array(),
				'country'       => array(),
			);

			$current = $data['current'];

			// Top pages data.
			if ( ! empty( $current['popular_pages'][0]['dimensions'] ) && ! empty( $current['popular_pages'][0]['metrics'] ) ) {
				$stats['page'] = array(
					'anchor'    => $this->get_anchor(
						$current['popular_pages'][0]['dimensions']['hostname'],
						$current['popular_pages'][0]['dimensions']['pagePathPlusQueryString'],
						$current['popular_pages'][0]['dimensions']['pageTitle'],
						true
					),
					'title'     => $current['popular_pages'][0]['dimensions']['pageTitle'],
					'pageviews' => $current['popular_pages'][0]['metrics']['screenPageViews'],
				);
			}

			// Top mediums data.
			if ( ! empty( $current['mediums'][0]['dimensions'] ) && ! empty( $current['mediums'][0]['metrics'] ) ) {
				$stats['medium'] = array(
					'name'     => $current['mediums'][0]['dimensions']['sessionDefaultChannelGrouping'],
					'sessions' => $current['mediums'][0]['metrics']['sessions'],
				);
			}

			// Top search engines data.
			if ( ! empty( $current['search_engines'][0]['dimensions'] ) && ! empty( $current['search_engines'][0]['metrics'] ) ) {
				$stats['search_engine'] = array(
					'name'     => ucfirst( $current['search_engines'][0]['dimensions']['sessionMedium'] ),
					'sessions' => $current['search_engines'][0]['metrics']['sessions'],
				);
			}

			// Top countries data.
			if ( ! empty( $current['top_countries'][0]['dimensions'] ) && ! empty( $current['top_countries'][0]['metrics'] ) ) {
				$stats['country'] = array(
					$current['top_countries'][0]['dimensions']['country'],
					$current['top_countries'][0]['dimensions']['countryId'],
					$current['top_countries'][0]['metrics']['screenPageViews'],
				);
			}
		}

		return $stats;
	}

	/**
	 * Format the data for all stats page.
	 *
	 * Get the required data from Google response and format.
	 *
	 * @since 3.4.0
	 *
	 * @param array $data Stats data from Google.
	 *
	 * @return array $stats
	 */
	private function stats_page( $data ) {
		$stats = array();

		// Return early when don't get the data we deserve.
		if ( empty( $data ) ) {
			return $stats;
		}

		// Format summary data.
		if ( isset( $data['multiple']['summary'] ) ) {
			$stats['summary'] = $this->summary( $data['multiple']['summary'] );

			// Format countries list.
			if ( isset( $data['current']['top_countries'] ) ) {
				$country_count = 0;

				foreach ( $data['current']['top_countries'] as $country ) {
					// Top countries full details.
					$stats['countries'][] = array(
						$country['dimensions']['country'],
						$country['dimensions']['countryId'],
						$country['metrics']['screenPageViews'],
					);

					// Add top country to summary.
					if ( 0 === $country_count ) {
						$stats['summary']['country'] = array(
							'value'     => $country['dimensions']['country'],
							'code'      => $country['dimensions']['countryId'],
							'pageviews' => $country['metrics']['screenPageViews'],
						);
					}

					$country_count ++;
				}
			}

			// Format sources and mediums list.
			if ( isset( $data['current']['mediums'] ) ) {
				$medium_count = 0;

				foreach ( $data['current']['mediums'] as $medium ) {
					// Medium data.
					$stats['mediums'][] = array( $medium['dimensions']['sessionDefaultChannelGrouping'], $medium['metrics']['sessions'] );

					// Add top medium to summary.
					if ( 0 === $medium_count ) {
						$stats['summary']['medium'] = array(
							'value'    => $medium['dimensions']['sessionDefaultChannelGrouping'],
							'sessions' => $medium['metrics']['sessions'],
						);
					}

					$medium_count ++;
				}
			}

			// Format sources and mediums list.
			if ( isset( $data['current']['search_engines'] ) ) {
				$search_engine_count = 0;

				foreach ( $data['current']['search_engines'] as $search_engine ) {
					// Search engine data.
					$stats['search_engines'][] = array(
						ucfirst( $search_engine['dimensions']['sessionSource'] ),
						$search_engine['metrics']['sessions'],
					);

					// Add top search engine to summary.
					if ( 0 === $search_engine_count ) {
						$stats['summary']['search_engine'] = array(
							'value'    => ucfirst( $search_engine['dimensions']['sessionSource'] ),
							'sessions' => $search_engine['metrics']['sessions'],
						);
					}

					$search_engine_count ++;
				}
			}

			// Format sources and mediums list.
			if ( isset( $data['current']['social_networks'] ) ) {
				$social_network_count = 0;

				foreach ( $data['current']['social_networks'] as $social_network ) {
					// Social network data.
					$stats['social_networks'][] = array( $social_network['dimensions']['sourceMedium'], $social_network['metrics']['sessions'] );

					// Add top social network to summary.
					if ( 0 === $social_network_count ) {
						$stats['summary']['social_network'] = array(
							'value'    => $social_network['dimensions']['sourceMedium'],
							'sessions' => $social_network['metrics']['sessions'],
						);
					}

					$social_network_count ++;
				}
			}

			// Format pages list.
			if ( isset( $data['multiple']['top_pages'] ) ) {
				$page_count = 0;
				$pages      = array();

				// @todo Avoid double foreach loop.
				foreach ( $data['multiple']['top_pages'] as $page ) {
					if ( ! isset( $pages[ $page['dimensions']['pagePath'] ] ) ) {
						$pages[ $page['dimensions']['pagePath'] ] = array();
					}

					if ( isset( $page['dimensions']['dateRange'] ) && 'date_range_0' === $page['dimensions']['dateRange'] ) {
						// Top page full details.
						$pages[ $page['dimensions']['pagePath'] ]['link']    = $this->get_anchor( $page['dimensions']['hostname'], $page['dimensions']['pagePath'], $page['dimensions']['pageTitle'] );
						$pages[ $page['dimensions']['pagePath'] ]['session'] = $this->get_time( $page['metrics']['averageSessionDuration'], 'string' );
						$pages[ $page['dimensions']['pagePath'] ]['current'] = $page['metrics']['screenPageViews'];

						// Add top page to summary.
						if ( 0 === $page_count ) {
							$stats['summary']['page'] = array(
								'value'     => $page['dimensions']['pageTitle'],
								'html'      => $this->get_anchor( $page['dimensions']['hostname'], $page['dimensions']['pagePath'], $page['dimensions']['pageTitle'], true ),
								'pageviews' => $page['metrics']['screenPageViews'],
							);
						}

						$page_count ++;
					} elseif ( isset( $page['dimensions']['dateRange'] ) && 'date_range_1' === $page['dimensions']['dateRange'] ) {
						$pages[ $page['dimensions']['pagePath'] ]['previous'] = $page['metrics']['screenPageViews'];
					}
				}

				// @todo Avoid double foreach loop.
				foreach ( $pages as $page ) {
					// Top countries full details.
					if ( isset( $page['link'] ) ) {
						$stats['pages'][] = array(
							$page['link'],
							isset( $page['session'] ) ? $page['session'] : 0,
							isset( $page['current'] ) ? $page['current'] : 0,
							$this->trend_value(
								'screenPageViews',
								isset( $page['current'] ) ? $page['current'] : 0, // Current period value.
								isset( $page['previous'] ) ? $page['previous'] : 0 // Previous period value.
							),
						);
					}
				}
			}

			// Format sessions list.
			if ( isset( $data['current']['sessions'], $data['previous']['sessions'] ) ) {
				$stats['sessions'] = $this->setup_periodic_values( $data['current']['sessions'], $data['previous']['sessions'], 'sessions' );
			}

			// Format users list.
			if ( isset( $data['current']['users'], $data['previous']['users'] ) ) {
				$stats['users'] = $this->setup_periodic_values( $data['current']['users'], $data['previous']['users'], 'totalUsers' );
			}

			// Format page views list.
			if ( isset( $data['current']['pageviews'], $data['previous']['pageviews'] ) ) {
				$stats['pageviews'] = $this->setup_periodic_values( $data['current']['pageviews'], $data['previous']['pageviews'], 'screenPageViews' );
			}

			// Format pages per sessions list.
			if ( isset( $data['current']['page_sessions'], $data['previous']['page_sessions'] ) ) {
				$stats['page_sessions'] = $this->setup_periodic_values( $data['current']['page_sessions'], $data['previous']['page_sessions'], 'screenPageViewsPerSession' );
			}

			// Format average sessions list.
			if ( isset( $data['current']['average_sessions'], $data['previous']['average_sessions'] ) ) {
				$stats['average_sessions'] = $this->setup_periodic_values( $data['current']['average_sessions'], $data['previous']['average_sessions'], 'averageSessionDuration' );
			}

			// Format bounce rates list.
			if ( isset( $data['current']['bounce_rates'], $data['previous']['bounce_rates'] ) ) {
				$stats['bounce_rates'] = $this->setup_periodic_values( $data['current']['bounce_rates'], $data['previous']['bounce_rates'], 'bounceRate' );
			}
		}

		return $stats;
	}

	/**
	 * Format the summary data to required format.
	 *
	 * @since 3.4.0
	 *
	 * @param array $data Report data from Google.
	 *
	 * @return array
	 */
	private function summary( $data ) {
		// Summary data should be single array.
		$current  = isset( $data[0]['metrics'] ) ? $data[0]['metrics'] : array();
		$previous = isset( $data[1]['metrics'] ) ? $data[1]['metrics'] : array();

		// Format summary data.
		$summary = array(
			'sessions'         => array(
				'value'    => isset( $current['sessions'] ) ? $current['sessions'] : 0,
				'previous' => isset( $previous['sessions'] ) ? $previous['sessions'] : 0,
			),
			'users'            => array(
				'value'    => isset( $current['totalUsers'] ) ? $current['totalUsers'] : 0,
				'previous' => isset( $previous['totalUsers'] ) ? $previous['totalUsers'] : 0,
			),
			'pageviews'        => array(
				'value'    => isset( $current['screenPageViews'] ) ? $current['screenPageViews'] : 0,
				'previous' => isset( $previous['screenPageViews'] ) ? $previous['screenPageViews'] : 0,
			),
			'page_sessions'    => array(
				'value'    => isset( $current['screenPageViewsPerSession'] ) ? $current['screenPageViewsPerSession'] : 0,
				'previous' => isset( $previous['screenPageViewsPerSession'] ) ? $previous['screenPageViewsPerSession'] : 0,
			),
			'average_sessions' => array(
				'value'    => isset( $current['averageSessionDuration'] ) ? $this->get_time( $current['averageSessionDuration'], 'string' ) : '00:00:00',
				'previous' => isset( $previous['averageSessionDuration'] ) ? $this->get_time( $previous['averageSessionDuration'], 'string' ) : '00:00:00',
			),
			'bounce_rates'     => array(
				'value'    => isset( $current['bounceRate'] ) ? $current['bounceRate'] : 0,
				'previous' => isset( $previous['bounceRate'] ) ? $previous['bounceRate'] : 0,
			),
			'new_users'        => array(
				'value'    => isset( $current['newUsers'] ) ? $current['newUsers'] : 0,
				'previous' => isset( $previous['newUsers'] ) ? $previous['newUsers'] : 0,
			),
		);

		// User sessions.
		$summary['user_sessions'] = $this->get_user_percentage( $summary['users']['value'], $summary['new_users']['value'] );

		// Now set the trends.
		$summary['users']['trend']            = $this->trend_value( 'users', $summary['users']['value'], $summary['users']['previous'] );
		$summary['sessions']['trend']         = $this->trend_value( 'sessions', $summary['sessions']['value'], $summary['sessions']['previous'] );
		$summary['pageviews']['trend']        = $this->trend_value( 'screenPageViews', $summary['pageviews']['value'], $summary['pageviews']['previous'] );
		$summary['new_users']['trend']        = $this->trend_value( 'newUsers', $summary['new_users']['value'], $summary['new_users']['previous'] );
		$summary['bounce_rates']['trend']     = $this->trend_value( 'bounceRate', $summary['bounce_rates']['value'], $summary['bounce_rates']['previous'] );
		$summary['page_sessions']['trend']    = $this->trend_value( 'screenPageViewsPerSession', $summary['page_sessions']['value'], $summary['page_sessions']['previous'] );
		$summary['average_sessions']['trend'] = $this->trend_value( 'averageSessionDuration', $summary['average_sessions']['value'], $summary['average_sessions']['previous'] );

		return $summary;
	}

	/**
	 * Calculate new vs returning users using available values.
	 *
	 * @since 3.4.0
	 *
	 * @param int $total Total users.
	 * @param int $new   New users.
	 *
	 * @return float[]|int[]
	 */
	private function get_user_percentage( $total, $new ) {
		$values = array(
			'new'       => 0,
			'returning' => 0,
		);

		// Total can not be less than new.
		if ( empty( $total ) || $total < $new ) {
			return $values;
		}

		// Calculate new value percentage.
		$new = $new / ( $total / 100 );

		return array(
			'new'       => (float) number_format( $new, 2 ),
			'returning' => (float) number_format( 100 - $new, 2 ),
		);
	}

	/**
	 * Format different values returned from API.
	 *
	 * @since 3.4.0
	 *
	 * @param string $name  Metric of Dimension name.
	 * @param mixed  $value Field value.
	 *
	 * @return mixed
	 */
	private function format_value( $name, $value ) {
		switch ( $name ) {
			// Normal numbers.
			case 'sessions':
			case 'totalUsers':
			case 'newUsers':
			case 'screenPageViews':
				$value = (int) $value;
				break;

			// With decimals.
			case 'percentNewSessions':
			case 'bounceRate':
			case 'screenPageViewsPerSession':
				$value = (float) number_format( $value, 2 );
				break;

			// Year and month.
			case 'month':
				// Create a DateTime object from the Ym format.
				$date = \DateTime::createFromFormat( 'm', $value );
				// Use the d/m/Y format.
				$value = $date->format( 'M' );
				break;

			// Year and week.
			case 'week':
				// Get year from the string.
				$year = substr( $value, 0, 4 );
				// Get week number from the string.
				$week = substr( $value, 4, 2 );

				try {
					$dto = new \DateTime();
					// Setup date.
					$dto->setISODate( $year, $week );
					// Week start date.
					$start = $dto->format( 'j M' );
					$dto->modify( '+6 days' );
					// Week end date.
					$end = $dto->format( 'j M' );

					// Return formatted value.
					$value = $start . ' - ' . $end;
				} catch ( \Exception $e ) {
					// Return formatted value.
					$value = '-';
				}
				break;

			// Hour.
			case 'dateHour':
				// Create a DateTime object from the Ym format.
				$date = \DateTime::createFromFormat( 'YmdH', $value );
				// Use the d/m/Y format.
				$value = $date->format( 'ga, D, M j, Y' );
				break;

			// Date format.
			case 'date':
				// Create a DateTime object from the Ymd format.
				$date = \DateTime::createFromFormat( 'Ymd', $value );
				// Use the d/m/Y format.
				$value = $date->format( 'M j' );
				break;

			// Group others.
			case 'sourceMedium':
			case 'sessionDefaultChannelGrouping':
				// Make it as 'others'.
				if ( '(not set)' === $value ) {
					$value = __( 'Other', 'ga_trans' );
				}
				break;

			// Link data.
			case 'hostname':
			case 'pageTitle':
			case 'pagePath':
			case 'pagePathPlusQueryString':
				// Value not found.
				if ( '(not set)' === $value ) {
					$value = '';
				}
				break;
		}

		return $value;
	}

	/**
	 * Format the data to get the previous period data separately.
	 *
	 * GA API will return date comparison data in weird format. We need
	 * to separate them for our convenience.
	 * Please note, the count of current and previous data should be same.
	 *
	 * @since 3.4.0
	 *
	 * @param array  $current  Current period data.
	 * @param array  $previous Previous period data.
	 * @param string $type     Metrics type.
	 *
	 * @return array
	 */
	protected function setup_periodic_values( $current, $previous, $type ) {
		$stats = array();

		// Total no. of items.
		$total_count = count( $current );

		// Loop through all items.
		for ( $i = 0; $i < $total_count; $i ++ ) {
			// Setup the period values.
			$current_date = $this->get_period_value( $current[ $i ] );

			if ( isset( $previous[ $i ] ) ) {
				$previous_data = $previous[ $i ]['metrics'][ $type ];
				$previous_date = $this->get_period_value( $previous[ $i ] );
			} else {
				$previous_data = '';
				$previous_date = '';
			}

			// Current period data.
			$stats['current'][ $i ] = array(
				$current_date,
				$this->format_periodic_value( $type, $current[ $i ]['metrics'][ $type ], 'string' ),
				$this->trend_value(
					$type,
					$this->format_periodic_value( $type, $current[ $i ]['metrics'][ $type ], 'string' ),
					$this->format_periodic_value( $type, $previous_data, 'string' )
				),
			);

			// Previous period data.
			$stats['previous'][ $i ] = array(
				$previous_date,
				$this->format_periodic_value( $type, $previous_data ),
			);
		}

		return $stats;
	}
}
