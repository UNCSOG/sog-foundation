<?php

namespace OTGS\Toolset\CRED\Controller\ExpirationManager\Post;

use OTGS\Toolset\CRED\Controller\ExpirationManager\Post as PostExpirationManager;
use OTGS\Toolset\CRED\Controller\ExpirationManager\Post\Singular as SingularPostExpirationManager;
use OTGS\Toolset\CRED\Controller\ExpirationManager\Post\Cron as CronManager;

/**
 * Controller for executing expiration for posts.
 *
 * @since 2.3
 */
class Action {

	const CUSTOM_ACTIONS_FILTER_HANDLE = 'cred_post_expiration_custom_actions';

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
	}

	/**
	 * Add hooks.
	 *
	 * @since 2.3
	 */
	private function add_hooks() {
		// Fire custom action in the right event: 11
		// So notifications on posts to be expired can happen at 10
		add_action( CronManager::EVENT_NAME, array( $this, 'do_scheduled_action' ), 11 );
	}

	/**
	 * Check the existence of expired posts and do expire them.
	 *
	 * @since 2.3
	 */
	public function do_scheduled_action() {
		global $wpdb;
		$posts_expired = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m1.post_id, m2.meta_value AS action
					FROM $wpdb->postmeta m1 INNER JOIN $wpdb->postmeta m2
					ON m1.post_id = m2.post_id
					AND m1.meta_key = %s
					AND m2.meta_key = %s
					WHERE m1.meta_value != 0
					AND m1.meta_value < %d",
				array(
					SingularPostExpirationManager::POST_META_TIME,
					SingularPostExpirationManager::POST_META_ACTION,
					time(),
				)
			)
		);

		if ( empty( $posts_expired ) ) {
			return;
		}

		// Disable notifications so they do not get fired on status updates
		do_action( 'toolset_forms_remove_notifications_trigger_hooks' );

		$posts_expired_ids = array();

		foreach ( $posts_expired as $post_meta ) {
			$posts_expired_ids[] = $post_meta->post_id;
			$post_meta->action = maybe_unserialize( $post_meta->action );

			$post_status_action = toolset_getarr( $post_meta->action, 'post_status', 'original' );

			if ( 'trash' == $post_status_action ) {
				wp_trash_post( $post_meta->post_id );
			} else if ( 'original' !== $post_status_action ) {
				wp_update_post( array(
					'ID' => $post_meta->post_id,
					'post_status' => $post_status_action
				) );
			}

			// Run custom actions
			$custom_actions = toolset_getarr( $post_meta->action, 'custom_actions', array() );
			foreach ( $custom_actions as $action ) {
				if ( ! empty( $action['meta_key'] ) ) {
					update_post_meta( $post_meta->post_id, $action['meta_key'], toolset_getarr( $action, 'meta_value', '' ) );
				}
			}
		}

		// Restore notifications
		do_action( 'toolset_forms_add_notifications_trigger_hooks' );

		if ( ! empty( $posts_expired_ids ) ) {
			$posts_expired_ids = implode( ',', $posts_expired_ids );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $wpdb->postmeta
					SET meta_value = 0
					WHERE post_id IN ({$posts_expired_ids})
					AND meta_key = %s",
					SingularPostExpirationManager::POST_META_TIME
				)
			);
		}
	}

}
