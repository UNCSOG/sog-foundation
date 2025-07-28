<?php
/**
 * The Google analytics helper class.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics
 */

namespace Beehive\Core\Modules\Google_Analytics;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Helpers\General;
use Beehive\Core\Utils\Abstracts\Base;
use Beehive\Core\Modules\Google_Auth\Helper as Auth_Helper;

/**
 * Class Helper
 *
 * @package Beehive\Core\Modules\Google_Analytics
 */
class Helper extends Base {

	/**
	 * Get the post types where we can show analytics meta box.
	 *
	 * Use `beehive_google_analytics_post_types` filter to add support
	 * for another custom post type.
	 *
	 * @return array
	 * @since 3.2.0
	 */
	public function post_types() {
		// Post types to show.
		$post_types = (array) beehive_analytics()->settings->get(
			'post_types',
			'tracking',
			false,
			array()
		);

		/**
		 * Filter to add/remove custom post types from analytics meta box.
		 *
		 * Use this filter to show stats data in a custom post type edit screen.
		 *
		 * @param array $post_types Post types.
		 *
		 * @since 3.2.0
		 */
		return apply_filters( 'beehive_google_analytics_post_types', $post_types );
	}

	/**
	 * Check if current site can see analytics data.
	 *
	 * If current site is not logged in, we can access the stats
	 * data using network creds. Only in multisite.
	 *
	 * @param bool           $network Network flag.
	 * @param \Exception|bool $exception Exception if any.
	 *
	 * @return bool
	 * @since 3.2.0
	 */
	public function can_get_stats( $network = false, &$exception = false ) {
		$show_exception = true;

		// Get current source.
		$source = $this->login_source( $network );

		// Network flag.
		$network = 'network' === $source;

		// Try to get the logged in status.
		$can = Auth_Helper::instance()->is_logged_in( $network );

		if ( $can ) {
			$show_exception = false;
			// One account should be selected.
			$can = $this->is_account_selected( $network );
		}

		/**
		 * Filter hook to modify the stats cap flag.
		 *
		 * @param bool $can Can get stats.
		 *
		 * @since 3.2.0
		 */
		$can = apply_filters( 'beehive_google_can_get_stats', $can );

		// Setup an exception for not logged in status.
		if ( ! $can && $show_exception ) {
			$exception = new \Exception( __( 'You need to authenticate with your Google account to enable access to statistics.', 'ga_trans' ) );
		}

		return $can;
	}

	/**
	 * Check if an account is selected in settings.
	 *
	 * A stream or profile should be selected for analytics requests.
	 * This will always be false if not logged in.
	 *
	 * @param bool $network Network flag.
	 *
	 * @return bool
	 * @since 3.2.0
	 */
	public function is_account_selected( $network = false ) {
		// Decide login source.
		$network = $this->login_source( $network ) === 'network';

		// Selected stream.
		$account = beehive_analytics()->settings->get( 'stream', 'google', $network );

		return ! empty( $account );
	}

	/**
	 * Check if current site can see analytics data.
	 *
	 * If current site is not logged in, we can access the stats
	 * data using network creds. Only in multisite.
	 * When network admin is already logged in and subsite admin not,
	 * we can use network admin's login to get stats for the subsite.
	 * But subsite admins can see only their site's stats.
	 *
	 * @param bool $network Network flag.
	 *
	 * @return string
	 * @since 3.2.0
	 */
	public function login_source( $network = false ) {
		// Default source is single site.
		$source = 'single';

		// Only valid if multisite.
		if ( is_multisite() ) {
			// Network admin always require network credentials.
			if ( $network ) {
				$source = 'network';
			} else {
				// Is plugin active network wide.
				$network_wide = General::is_networkwide();

				// Login flag for single site.
				$loggedin = Auth_Helper::instance()->is_logged_in();
				// Login status network wide.
				$network_loggedin = Auth_Helper::instance()->is_logged_in( true );

				// Network admin logged in, subsite admin not.
				if ( ! $loggedin && $network_loggedin && $network_wide ) {
					$source = 'network';
				}
			}
		}

		/**
		 * Filter the login source for analytics report.
		 *
		 * @param string $source Source (network or single).
		 *
		 * @since 3.2.0
		 */
		return apply_filters( 'beehive_google_analytics_login_source', $source );
	}

	/**
	 * Check if current page is Google Analytics admin page.
	 *
	 * @return bool
	 * @since 3.3.5 Changed method name.
	 *
	 * @since 3.3.0
	 */
	public static function is_ga_admin() {
		// Get current screen id.
		$current_screen = get_current_screen();

		// Check if current page is our plugin ga settings page.
		// Using strpos to support translation - https://incsub.atlassian.net/browse/BEE-15.
		return isset( $current_screen->id ) && (
				strpos( $current_screen->id, 'page_beehive-google-analytics' ) || strpos( $current_screen->id, 'page_beehive-statistics' )
			);
	}

	/**
	 * Get the previous date data from the current period.
	 *
	 * @param string $from From date.
	 * @param string $to To date.
	 * @param string $format Date format.
	 *
	 * @return array
	 * @since 3.2.7 Moved to helper.
	 *
	 * @since 3.2.0
	 */
	public static function get_previous_period( $from, $to, $format = 'Y-m-d' ) {
		try {
			// Make sure the dates are in proper format.
			$from = gmdate( $format, strtotime( $from ) );
			$to   = gmdate( $format, strtotime( $to ) );

			// Create date objects from the periods.
			$date_from = date_create( $from );
			$date_to   = date_create( $to );
			// Get the difference between periods.
			$days = (int) date_diff( $date_from, $date_to )->days;

			if ( $days > 0 ) {
				$previous_from = gmdate( $format, strtotime( $from . ' -' . ( $days + 1 ) . ' days' ) );
				$previous_to   = gmdate( $format, strtotime( $from . ' -1 days' ) );
			} else {
				$previous_from = gmdate( $format, strtotime( $from . ' -1 days' ) );
				$previous_to   = $previous_from;
			}
		} catch ( \Exception $e ) {
			$previous_from = false;
			$previous_to   = false;
		}

		return array(
			'from' => $previous_from,
			'to'   => $previous_to,
		);
	}

	/**
	 * Get the all statistics page url.
	 *
	 * @param bool $network Network flag.
	 *
	 * @return string
	 * @since 3.3.0
	 */
	public static function statistics_url( $network = false ) {
		// Get base url.
		$url = $network ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' );

		// Get statistics menu status.
		$main_menu = beehive_analytics()->settings->get(
			'statistics_menu',
			'general',
			is_network_admin()
		);

		// Get statistics page slug.
		$page = $main_menu ? 'beehive-statistics' : 'beehive-google-analytics';

		$url = add_query_arg(
			array(
				'page' => $page,
			),
			$url
		);

		// Append tab.
		if ( ! $main_menu ) {
			$url = $url . '#/statistics';
		}

		/**
		 * Filter to modify GA statistics url
		 *
		 * @param bool $network Network flag.
		 *
		 * @param string $url Statistics url.
		 *
		 * @since 3.3.0
		 */
		return apply_filters( 'beehive_ga_statistics_url', $url, $network );
	}

	/**
	 * Get the GA settings url.
	 *
	 * @param string $tab Tab.
	 * @param bool   $network Network flag.
	 *
	 * @return string
	 * @since 3.3.0
	 */
	public static function settings_url( $tab = 'account', $network = false ) {
		// Get base url.
		$url = $network ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' );

		// Get page.
		$url = add_query_arg(
			array(
				'page' => 'beehive-google-analytics',
			),
			$url
		);

		// Append tab.
		$url = $url . '#/' . $tab;

		/**
		 * Filter to modify main url used to build GA settings url
		 *
		 * @param bool $network Network flag.
		 *
		 * @param string $url Settings URL.
		 *
		 * @since 3.3.0
		 */
		return apply_filters( 'beehive_ga_settings_url', $url, $network );
	}

	/**
	 * Log API error to the db.
	 *
	 * @param int    $code Error code.
	 * @param string $message Error message.
	 * @param bool   $network Network flag.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public static function log_error( $code, $message, $network = false ) {
		$error = array(
			'code'    => $code,
			'type'    => 'ga4',
			'message' => '',
		);

		/**
		 * Filter to modify main url used to build GA settings url
		 *
		 * @param array $error Error data.
		 * @param int $code Error code.
		 * @param string $message Error message.
		 * @param bool $network Network flag.
		 *
		 * @since 3.4.0
		 */
		$error = apply_filters( 'beehive_ga_log_error', $error, $code, $message, $network );

		// Update API error.
		beehive_analytics()->settings->update( 'api_error', $error, 'google', $network );
	}
}
