<?php

namespace OTGS\Toolset\CRED\Controller\ExpirationManager\Post;

use OTGS\Toolset\CRED\Controller\ExpirationManager\Post as PostExpirationManager;

/**
 * Controller for cron jobs on post expirations.
 *
 * @since 2.3
 */
class Cron {

	const EVENT_NAME = 'cred_post_expiration_event';

	const TIME_FIELD = '_cred_post_expiration_time';
	const ACTION_FIELD = '_cred_post_expiration_action';

	protected $manager;

	/**
	 * Manager constructor.
	 *
	 * @since 2.3
	 */
	public function __construct(
		PostExpirationManager $manager
	) {
		$this->manager = $manager;
	}

	/**
	 * Initialize the manager.
	 *
	 * @since 2.3
	 */
	public function initialize() {
		$this->add_hooks();
		// This used to happen in init, maybe it can not happen earlier!?
		$this->setup_schedule();
	}

	/**
	 * Add hooks.
	 *
	 * @since 2.3
	 */
	private function add_hooks() {
		// Register custom schedules
		add_filter( 'cron_schedules', array( $this, 'register_schedules' ), 10, 1 );
		// API hooks
		add_action( 'toolset_forms_setup_post_expiration_schedule', array( $this, 'setup_schedule' ) );
		add_action( 'toolset_forms_clear_post_expiration_schedule', array( $this, 'clear_schedule' ) );
	}

	/**
	 * Add custom schedules to the WordPress cron.
     *
	 * @param $schedules Existing shedules. The default schedules defined in core are:
	 *     'hourly'     => array( 'interval' => HOUR_IN_SECONDS,      'display' => __( 'Once Hourly' ) )
	 *     'twicedaily' => array( 'interval' => 12 * HOUR_IN_SECONDS, 'display' => __( 'Twice Daily' ) )
	 *     'daily'      => array( 'interval' => DAY_IN_SECONDS,       'display' => __( 'Once Daily' ) )
	 * @return array
	 * @since 2.3
	 */
	public function register_schedules( $schedules ) {
		/**
		 * Extend the cron schedules registered with Forms.
		 *
		 * @param array $schedules
		 * @since unknown
		 */
		$schedules = apply_filters( 'cred_post_expiration_cron_schedules', $schedules );

		return $schedules;
	}

	/**
	 * Setup a schedule for checking expired posts.
	 *
	 * @since 2.3
	 */
	public function setup_schedule() {
		$settings = $this->manager->get_settings();

		if ( isset( $settings['post_expiration_cron']['schedule'] ) ) {
			$schedule = wp_get_schedule( self::EVENT_NAME );
			if ( $schedule != $settings['post_expiration_cron']['schedule'] ) {
				$this->clear_schedule();
				wp_schedule_event(
					time(),
					$settings['post_expiration_cron']['schedule'],
					self::EVENT_NAME
				);
			}
		} else {
			$this->clear_schedule();
		}
	}

	/**
	 * Clear the schedule to check for expired posts.
	 *
	 * @since 2.3
	 */
	public function clear_schedule() {
		wp_clear_scheduled_hook( self::EVENT_NAME );
	}

}
