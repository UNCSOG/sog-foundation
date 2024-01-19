<?php

namespace OTGS\Toolset\CRED\Controller\DraftsManager\Post;

use OTGS\Toolset\CRED\Controller\DraftsManager\Post as PostDraftsManager;

class Action {

	/** @var Settings */
	private $settings_manager;

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
		add_action( PostDraftsManager::CUSTOM_HOOK, array( $this, 'do_scheduled_action' ) );
	}

	/**
	 * Delete post drafts if using the custom hook
	 */
	public function do_scheduled_action() {
		$post_draft_settings = $this->settings_manager->get_settings();
		$threshold           = toolset_getarr( $post_draft_settings, 'threshold', PostDraftsManager::SETTING_DEFAULT_THRESHOLD );
		$date_threshold      = date( "Y-m-d H:i:s", strtotime( $threshold ) );
		global $wpdb;

		// Cleanup old auto-drafts more than $threshold old.
		$old_posts = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts
				WHERE post_status = 'auto-draft'
				AND post_date < %s",
				array(
					$date_threshold,
				)
			)
		);
		foreach ( (array) $old_posts as $delete ) {
			// Force delete.
			wp_delete_post( $delete, true );
		}
	}

}
