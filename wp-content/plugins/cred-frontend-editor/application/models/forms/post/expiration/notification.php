<?php

namespace OTGS\Toolset\CRED\Model\Forms\Post\Expiration;

/**
 * Post expiration notification model.
 *
 * @since 2.3
 */
class Notification {

	private $settings;

	/**
	 * Model constructor
	 *
	 * @param array $settings
	 * @since 2.3
	 */
	public function __construct( $settings ) {
		$this->settings = $this->apply_defaults( $this->get_default_settings(), $settings );
	}

	/**
	 * Get the default settings that an expiration notification must hold.
	 *
	 * @return array
	 * @since 2.3
	 */
	private function get_default_settings() {
		return array(
			'form_id' => null,
			'event' => array(
				'expiration_date' => 0,
				'expiration_period' => DAY_IN_SECONDS
			),
		);
	}

	/**
	 * Apply defaults to post expiration notifications.
	 *
	 * @param array $defaults
	 * @param array $settings
	 * @return array
	 * @since 2.3
	 */
	private function apply_defaults( $defaults, $settings ) {
		$merged = $defaults;
		foreach ( $settings as $key => $value ) {
			if ( is_array( $value ) && isset( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
				$merged[ $key ] = $this->apply_defaults( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Get the ID of the form that set this notification.
	 *
	 * @return int
	 * @since 2.3
	 */
	public function get_form_id() {
		return toolset_getarr( $this->settings, 'form_id' );
	}

	/**
	 * Get the raw notification settings.
	 *
	 * @return array
	 * @since 2.3
	 */
	public function get_raw_definition() {
		return $this->settings;
	}

	/**
	 * Get the notification expiration date, as the amount of expiration periods
	 * before or after the post expiration set to send this notification.
	 *
	 * @return int
	 * @since 2.3
	 */
	private function get_expiration_date() {
		return toolset_getnest( $this->settings, array( 'event', 'expiration_date' ) );
	}

	/**
	 * Get the notification expiration period, as the unit to calculate the time to
	 * send this notification before or after the post expires.
	 *
	 * Note that this period is calculated as a count of seconds for minutes, hours, days or weeks intervals.
	 *
	 * @return int
	 * @since 2.3
	 */
	private function get_expiration_period() {
		return toolset_getnest( $this->settings, array( 'event', 'expiration_period' ) );
	}

	/**
	 * Check whether an expiration notification is due given the post expiration time.
	 *
	 * @param int $post_expiration_time
	 * @return bool
	 * @since 2.3
	 */
	public function is_due( $post_expiration_time ) {
		$now = time();
		$notification_time = $post_expiration_time - ( $this->get_expiration_date() * $this->get_expiration_period() );

		return ( $notification_time <= $now );
	}
}
