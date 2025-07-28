<?php

namespace Gravity_Forms\Gravity_Forms_RECAPTCHA;

use GFCache, GFAddOn;

/**
 * Refresh token lock.
 *
 * Hosts the logic required to prevent repeated refresh token requests.
 *
 * @since 1.8.0
 */
class Refresh_Lock_Handler {

	/**
	 * How many seconds until the rate limiting cache lock is cleared.
	 *
	 * @since 1.8.0
	 *
	 * @var integer
	 */
	const RATE_LIMIT_CACHE_EXPIRATION_SECONDS = MINUTE_IN_SECONDS;

	/**
	 * How many failed refresh requests until the refresh is locked becayse of rate limiting.
	 *
	 * @since 1.8.0
	 *
	 * @var integer
	 */
	const RATE_LIMIT_FAILED_REQUEST_THRESHOLD = 3;

	/**
	 * How many seconds until the refresh in progress cache lock is cleared.
	 *
	 * @since 1.8.0
	 *
	 * @var integer
	 */
	const REFRESH_IN_PROGRESS_EXPIRATION_SECONDS = MINUTE_IN_SECONDS;

	/**
	 * Failed request count option key.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	protected $failed_requests_count_key = '';

	/**
	 * The refresh in progress cache key.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	protected $refresh_in_progress_lock_key = '';

	/**
	 * The rate limiting cache key.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	protected $rate_limit_lock_key = '';

	/**
	 * The reason why the refreshing could be locked.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	public $refresh_lock_reason = '';

	/**
	 * An instance of the add-on.
	 *
	 * @since 1.8.0
	 *
	 * @var GFAddOn
	 */
	protected $addon;

	/**
	 * Handler constructor.
	 *
	 * @since 1.8.0
	 *
	 * @param GFAddOn $addon The add-on the handler is being initialized for.
	 */
	public function __construct( $addon ) {
		$this->addon                        = $addon;
		$slug                               = $addon->get_slug();
		$this->failed_requests_count_key    = $slug . '_failed_refresh_token_requests_count';
		$this->refresh_in_progress_lock_key = $slug . '_refresh_lock';
		$this->rate_limit_lock_key          = $slug . '_rate_limit';
	}

	/**
	 * Checks if the rate limit cache key is set, and sets the lock reason if locked.
	 *
	 * After consecutive failed requests, a cache key is set to prevent more failed requests.
	 *
	 * @sicne 1.8.0
	 *
	 * @return bool
	 */
	protected function is_rate_limited() {
		$rate_limited = GFCache::get( $this->rate_limit_lock_key, $found );
		if ( $found && $rate_limited ) {
			$this->refresh_lock_reason = 'Refresh token request rate limit reached.';

			return true;
		}

		return false;
	}

	/**
	 * Checks if the threshold for failed refresh requests has been reached, sets the cache key if so.
	 *
	 * @since 1.8.0
	 */
	public function increment_rate_limit() {
		$failed_requests_count = intval( get_option( $this->failed_requests_count_key ) );
		if ( $failed_requests_count >= self::RATE_LIMIT_FAILED_REQUEST_THRESHOLD ) {
			$this->addon->log_debug( __METHOD__ . '(): Rate limit threshold reached, setting rate limit lock.' );
			GFCache::set( $this->rate_limit_lock_key, true, true, self::RATE_LIMIT_CACHE_EXPIRATION_SECONDS );
			update_option( $this->failed_requests_count_key, 0 );
		} else {
			$this->addon->log_debug( __METHOD__ . '(): Increasing failed requests count, current count: ' . $failed_requests_count );
			update_option( $this->failed_requests_count_key, $failed_requests_count + 1 );
		}
	}

	/**
	 * Checks if there is a request already being made for refreshing the token.
	 *
	 * @since 1.8.0
	 *
	 * @return bool
	 */
	public function is_locked() {
		$locked = GFCache::get( $this->refresh_in_progress_lock_key, $found );
		if ( $found && $locked ) {
			$this->refresh_lock_reason = 'Token Refresh is already in progress.';

			return true;
		}

		return false;
	}

	/**
	 * Sets the refresh in progress cache lock.
	 *
	 * @since 1.8.0
	 */
	public function lock() {
		GFCache::set( $this->refresh_in_progress_lock_key, true, true, self::REFRESH_IN_PROGRESS_EXPIRATION_SECONDS );
		$this->addon->log_debug( __METHOD__ . '(): Refresh in progress lock has been set.' );
	}

	/**
	 * Clears the rate limit lock.
	 *
	 * @since 1.8.0
	 */
	public function reset_rate_limit() {
		GFCache::delete( $this->rate_limit_lock_key );
		update_option( $this->failed_requests_count_key, 0 );
		$this->addon->log_debug( __METHOD__ . '(): Rate limit lock cleared.' );
	}

	/**
	 * Clears the refresh in progress lock.
	 *
	 * @since 1.8.0
	 */
	public function release_lock() {
		GFCache::delete( $this->refresh_in_progress_lock_key );
		$this->addon->log_debug( __METHOD__ . '(): Refresh in progress lock cleared.' );
	}

	/**
	 * Checks the token can be refreshed after making sure no locks are in place.
	 *
	 * @since 1.8.0
	 *
	 * @return bool
	 */
	public function can_refresh_token() {
		return $this->is_rate_limited() === false && $this->is_locked() === false;
	}

}
