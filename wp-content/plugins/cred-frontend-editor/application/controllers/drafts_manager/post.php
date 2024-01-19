<?php

namespace OTGS\Toolset\CRED\Controller\DraftsManager;

use OTGS\Toolset\CRED\Model\Settings;

/**
 * Post expiration workflow controller.
 *
 * @since 2.3
 */
class Post {

	const SHORTCIRCUIT_CONSTANT = 'CRED_DISABLE_AUTOMATIC_CLEAR_POST_DRAFTS';

	const SETTING_KEY                          = 'clear_post_drafts';
	const SETTING_DISABLE_SCHEDULE             = 'never';
	const SETTING_DEFAULT_SCHEDULE             = 'daily';
	const SETTING_DEFAULT_SCHEDULE_IN_SECONDS  = 7 * DAY_IN_SECONDS;
	const SETTING_DEFAULT_THRESHOLD            = '-7 days';
	const SETTING_LEGACY_SCHEDULE              = 'every12hours';
	const SETTING_LEGACY_SCHEDULE_IN_SECONDS   = 12 * HOUR_IN_SECONDS;
	const SETTING_LEGACY_THRESHOLD             = '-6 hours';

	const NATIVE_HOOK = 'wp_scheduled_auto_draft_delete';
	const CUSTOM_HOOK = 'wp_cred_auto_draft_delete';

	/**
	 * @var Settings
	 */
	public $settings_model;

	/**
	 * @var \Toolset_Constants
	 */
	private $toolset_constants = null;

	/**
	 * Manager constructor
	 */
	public function __construct(
		Settings $settings_model,
		\Toolset_Constants $toolset_constants
	) {
		
		$this->settings_model    = $settings_model;
		$this->toolset_constants = $toolset_constants;
	}

	/**
	 * Initialize the manager
	 */
	public function initialize() {
		// Initialize the settings component
		// regardless of whether the feature is constant-halted:
		// we still need to print the srttings section
		$settings_manager = new Post\Settings( $this->settings_model, $this->toolset_constants );
		$settings_manager->initialize();

		if (
			$this->toolset_constants->defined( self::SHORTCIRCUIT_CONSTANT )
			&& true === $this->toolset_constants->constant( self::SHORTCIRCUIT_CONSTANT )
		) {
			wp_clear_scheduled_hook( self::CUSTOM_HOOK );
			return false;
		}

		// Initialize the action component
		$action = new Post\Action( $settings_manager );
		$action->initialize();

		// Initialize the cron component
		$cron_manager = new Post\Cron( $settings_manager );
		$cron_manager->initialize();

		return true;
	}

}
