<?php
/**
 * The data formatter class for Google stats.
 *
 * This class is little complex. Google Analytics Reporting API
 * response is really complex. We need to properly format the response.
 * Modify with caution.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Formatters
 */

namespace Beehive\Core\Modules\Google_Analytics\Stats\Formatters;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Utils\Abstracts\Base;

/**
 * Class Formatter
 *
 * @package Beehive\Core\Modules\Google_Analytics\Stats\Formatters
 */
abstract class Formatter extends Base {

	/**
	 * Create a anchor tag link from the given values.
	 *
	 * We need to make sure the anchor link is generated
	 * only when the host name is valid.
	 *
	 * @since 3.2.0
	 * @since 3.2.4 Added $user_title param.
	 *
	 * @param string $host      Host name.
	 * @param string $path      Page path.
	 * @param string $title     Page title.
	 * @param bool   $use_title Use title instead of link as anchor text.
	 *
	 * @return string
	 */
	protected function get_anchor( $host, $path, $title, $use_title = false ) {
		// Generate url from the data.
		$url = $this->get_link( $host, $path );

		// Only if url is generated.
		if ( empty( $url ) ) {
			return '';
		}

		$text = $use_title ? $title : $path;

		return '<a href="' . esc_url( $url ) . '" target="_blank" title="' . esc_attr( $title ) . '(' . esc_url( $url ) . ')">' . esc_attr( $text ) . '</a>';
	}

	/**
	 * Create a link from the given values.
	 *
	 * @since 3.2.0
	 *
	 * @param string $host Host name.
	 * @param string $path Page path.
	 *
	 * @return string
	 */
	protected function get_link( $host, $path ) {
		// We need valid host and title.
		if ( empty( $host ) || empty( $path ) ) {
			return '';
		}

		// Generate url from the data.
		return esc_url( 'http://' . $host . $path );
	}

	/**
	 * Format the time string to new format.
	 *
	 * We need time in string format as well as array of ints.
	 *
	 * @since 3.2.0
	 *
	 * @param string $value Time string.
	 * @param string $type  Time type.
	 *
	 * @return array|string
	 */
	protected function get_time( $value, $type = 'int' ) {
		// Get 3 value array of hour, minutes and seconds.
		if ( 'int' === $type ) {
			return array(
				(int) gmdate( 'H', $value ),
				(int) gmdate( 'i', $value ),
				(int) gmdate( 's', $value ),
			);
		}

		return gmdate( 'H:i:s', floor( $value ) );
	}

	/**
	 * Format the data to get the previous period data separately.
	 *
	 * GA API will return date comparison data in weird format. We need
	 * to separate them for our convenience.
	 * Please note, the count of current and previous data should be same.
	 *
	 * @since 3.2.0
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
				$previous_data = $previous[ $i ][ $type ];
				$previous_date = $this->get_period_value( $previous[ $i ] );
			} else {
				$previous_data = '';
				$previous_date = '';
			}

			// Current period data.
			$stats['current'][ $i ] = array(
				$current_date,
				$this->format_periodic_value( $type, $current[ $i ][ $type ], 'string' ),
				$this->trend_value(
					$type,
					$this->format_periodic_value( $type, $current[ $i ][ $type ], 'string' ),
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

	/**
	 * Format different values returned from API.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type      Metric type.
	 * @param mixed  $value     Field value.
	 * @param string $time_type Optional (Only for avg time).
	 *
	 * @return mixed
	 */
	protected function format_periodic_value( $type, $value, $time_type = 'int' ) {
		return $value;
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
	abstract protected function get_period_value( $data );

	/**
	 * Get the trend by calculating difference with previous period.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type     Value type.
	 * @param mixed  $current  Current value.
	 * @param mixed  $previous Previous period value.
	 *
	 * @return mixed
	 */
	protected function trend_value( $type, $current, $previous ) {
		switch ( $type ) {
			case 'bounceRate':
			case 'pageviewsPerSession':
			case 'pageviews':
			case 'sessions':
			case 'users':
			case 'newUsers':
			case 'screenPageViewsPerSession':
			case 'screenPageViews':
			case 'totalUsers':
				$current  = (float) $current;
				$previous = (float) $previous;
				// When previous value is empty, trend is 100%.
				if ( empty( $previous ) && ! empty( $current ) ) {
					$trend = 100;
				} elseif ( ! empty( $previous ) && empty( $current ) ) {
					// When current value is 0 and previous value is not, trend is -100.
					$trend = - 100;
				} else {
					if ( $current === $previous ) {
						$trend = 0;
					} else {
						$diff  = $current - $previous;
						$trend = ( $diff / $previous ) * 100;
					}
				}

				$value = round( $trend );
				break;
			// Time difference.
			case 'avgSessionDuration':
			case 'averageSessionDuration':
				// Convert to seconds.
				$current  = strtotime( $current ) - strtotime( '00:00:00' );
				$previous = strtotime( $previous ) - strtotime( '00:00:00' );

				// Now it's int, get the value.
				$value = $this->trend_value( 'sessions', $current, $previous );
				break;
			default:
				$value = 0;
		}

		return $value;
	}

	/**
	 * Format the numeric number to a simplest form (1k, 1m).
	 *
	 * @since 3.3.8
	 *
	 * @param int $number Number.
	 *
	 * @return string
	 */
	public function number_to_simple_text( $number ) {
		// Suffixes.
		$suffixes = array( '', 'k', 'm', 'g', 't' );

		$index = 0;

		while ( abs( $number ) >= 1000 && $index < 5 ) {
			$index ++;
			$number /= 1000;
		}

		// Get the number.
		$output = $number > 0 ? floor( $number * 1000 ) / 1000 : ceil( $number * 1000 ) / 1000;

		// Add suffix.
		$output = $output . $suffixes[ $index ];

		/**
		 * Filter to modify the number to simple text output.
		 *
		 * @since 3.3.8
		 *
		 * @param int    $number Number.
		 *
		 * @param string $output Output.
		 */
		return apply_filters( 'beehive_number_to_simple_text', $output, $number );
	}
}
