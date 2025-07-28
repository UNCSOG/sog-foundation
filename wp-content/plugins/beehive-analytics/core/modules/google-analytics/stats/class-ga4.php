<?php
/**
 * The Google analytics stats class.
 *
 * @link    http://wpmudev.com
 * @since   3.4.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics\Stats
 */

namespace Beehive\Core\Modules\Google_Analytics\Stats;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class GA4
 *
 * @package Beehive\Core\Modules\Google_Analytics\Stats
 */
class GA4 extends Stats {

	/**
	 * Statistics type.
	 *
	 * @since 3.4.5
	 * @var string $type
	 */
	protected $type = 'ga4';

	/**
	 * Generate custom cache key for the data.
	 *
	 * @param string $from Start date.
	 * @param string $to End date.
	 * @param string $type Stats type (stats, dashboard, front).
	 * @param bool $network Network flag.
	 * @param bool|string $account Account.
	 *
	 * @return string
	 * @since 3.4.0
	 *
	 */
	protected function cache_key( $from, $to, $type = 'stats', $network = false, $account = false ) {
		if ( empty( $account ) ) {
			$account = beehive_analytics()->settings->get( 'stream', 'google', $network, '' );
		}

		return parent::cache_key( $from, $to, $type . '_' . $this->type, $network, $account );
	}

	/**
	 * Get the request instance for the class.
	 *
	 * @return Requests\GA4
	 * @since 3.4.0
	 *
	 */
	public function request() {
		return Requests\GA4::instance();
	}

	/**
	 * Get the processor instance for the class.
	 *
	 * @return Processors\GA4
	 * @since 3.4.0
	 *
	 */
	public function processor() {
		return Processors\GA4::instance();
	}

	/**
	 * Get the formatter instance for the class.
	 *
	 * @return Formatters\GA4
	 * @since 3.4.0
	 *
	 */
	public function formatter() {
		return Formatters\GA4::instance();
	}
}
