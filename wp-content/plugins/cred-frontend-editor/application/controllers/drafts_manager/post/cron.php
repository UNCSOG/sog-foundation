<?php

namespace OTGS\Toolset\CRED\Controller\DraftsManager\Post;

use OTGS\Toolset\CRED\Controller\DraftsManager\Post as PostDraftsManager;

/**
 * Controller for cron jobs on post drafts management.
 */
class Cron {

	const SHEDULE_REQUIRED_NONE   = 'none';
	const SHEDULE_REQUIRED_NATIVE = 'native';
	const SHEDULE_REQUIRED_CUSTOM = 'custom';

	/** @var Settings */
	protected $settings_manager;

	/**
	 * Constructor
	 */
	public function __construct(
		Settings $settings_manager
	) {
		$this->settings_manager = $settings_manager;
	}

	/**
	 * Initialize the manager
	 */
	public function initialize() {
		$this->add_hooks();
	}

	/**
	 * Add hooks
	 */
	private function add_hooks() {
		// Register custom schedules
		add_filter( 'cron_schedules', array( $this, 'register_schedule' ) );
		add_action( 'wp', array( $this, 'maybe_setup_schedule' ) );
	}

	/**
	 * Add custom schedules to the WordPress cron.
     *
	 * @param $schedules Existing shedules. The default schedules defined in core are:
	 *     'hourly'     => array( 'interval' => HOUR_IN_SECONDS,      'display' => __( 'Once Hourly' ) )
	 *     'twicedaily' => array( 'interval' => 12 * HOUR_IN_SECONDS, 'display' => __( 'Twice Daily' ) )
	 *     'daily'      => array( 'interval' => DAY_IN_SECONDS,       'display' => __( 'Once Daily' ) )
	 * @return array
	 */
	public function register_schedule( $schedules ) {
		$post_draft_settings = $this->settings_manager->get_extended_settings();
		$schedule            = toolset_getarr( $post_draft_settings, 'schedule', PostDraftsManager::SETTING_DEFAULT_SCHEDULE );
		// There is a schedule named as ours already
		if ( false !== toolset_getarr( $schedules, $schedule, false ) ) {
			return $schedules;
		}

		$schedule_in_seconds = toolset_getarr( $post_draft_settings, 'schedule_in_seconds', PostDraftsManager::SETTING_DEFAULT_SCHEDULE_IN_SECONDS );

		$schedules[ $schedule ] = array(
			'interval' => $schedule_in_seconds,
			'display'  => __( 'As defined in Toolset Forms drafts management settings', 'wp_cred' )
		);
		return $schedules;
	}

	/**
	 * Decide whether a new schedule is required, and eventually set it up
	 */
	public function maybe_setup_schedule() {
		$schedule_setup_status = $this->needs_schedule_setup();
		if ( self::SHEDULE_REQUIRED_NONE === $schedule_setup_status ) {
			return false;
		}
		$this->setup_schedule( $schedule_setup_status );
		return true;
	}

	/**
	 * Clear all draft-related schedules
	 */
	private function clear_schedules() {
		wp_clear_scheduled_hook( PostDraftsManager::NATIVE_HOOK );
		wp_clear_scheduled_hook( PostDraftsManager::CUSTOM_HOOK );
	}

	/**
	 * Decide whethwe a new schedule needs to be set
	 *
	 * @return string
	 */
	private function needs_schedule_setup() {
		$post_draft_settings = $this->settings_manager->get_extended_settings();

		$schedule = toolset_getarr( $post_draft_settings, 'schedule', PostDraftsManager::SETTING_DEFAULT_SCHEDULE );

		if ( $schedule === PostDraftsManager::SETTING_DISABLE_SCHEDULE ) {
			// Keep the native one, if it exists.
			wp_clear_scheduled_hook( PostDraftsManager::CUSTOM_HOOK );
			return self::SHEDULE_REQUIRED_NONE;
		}

		$schedule_in_seconds = toolset_getarr( $post_draft_settings, 'schedule_in_seconds', PostDraftsManager::SETTING_DEFAULT_SCHEDULE_IN_SECONDS );
		$threshold           = toolset_getarr( $post_draft_settings, 'threshold', PostDraftsManager::SETTING_DEFAULT_THRESHOLD );

		if (
			$schedule === PostDraftsManager::SETTING_DEFAULT_SCHEDULE
			&& $threshold === PostDraftsManager::SETTING_DEFAULT_THRESHOLD
		) {
			$native_schedule = wp_get_schedule( PostDraftsManager::NATIVE_HOOK );
			if ( false !== $native_schedule ) {
				return self::SHEDULE_REQUIRED_NONE;
			}
			$this->clear_schedules();
			return self::SHEDULE_REQUIRED_NATIVE;
		}

		$custom_schedule = wp_get_schedule( PostDraftsManager::CUSTOM_HOOK );

		if ( $schedule === $custom_schedule ) {
			return self::SHEDULE_REQUIRED_NONE;
		}

		wp_clear_scheduled_hook( PostDraftsManager::CUSTOM_HOOK );
		return self::SHEDULE_REQUIRED_CUSTOM;
	}

	/**
	 * Setup a schedule for checking expired posts
	 */
	private function setup_schedule( $schedule_setup_status ) {
		if ( self::SHEDULE_REQUIRED_NATIVE === $schedule_setup_status ) {
			wp_schedule_event(
				time(),
				PostDraftsManager::SETTING_DEFAULT_SCHEDULE,
				PostDraftsManager::NATIVE_HOOK
			);
			return;
		}

		if ( self::SHEDULE_REQUIRED_CUSTOM !== $schedule_setup_status ) {
			return;
		}

		$post_draft_settings = $this->settings_manager->get_settings();
		$schedule            = toolset_getarr( $post_draft_settings, 'schedule', PostDraftsManager::SETTING_DEFAULT_SCHEDULE );

		wp_schedule_event(
			time(),
			$schedule,
			PostDraftsManager::CUSTOM_HOOK
		);
	}

}
