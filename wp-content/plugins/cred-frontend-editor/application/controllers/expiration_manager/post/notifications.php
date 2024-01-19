<?php

namespace OTGS\Toolset\CRED\Controller\ExpirationManager\Post;

use OTGS\Toolset\CRED\Controller\ExpirationManager\Post as PostExpirationManager;
use OTGS\Toolset\CRED\Controller\ExpirationManager\Post\Singular as SingularPostExpirationManager;
use OTGS\Toolset\CRED\Controller\ExpirationManager\Post\Cron as CronManager;

use OTGS\Toolset\CRED\Model\Forms\Post\Expiration\Notification as NotificationModel;

/**
 * Controller for executing expiration for posts.
 *
 * @since 2.3
 */
class Notifications {

	const FORM_META = '_cred_notification';
	const PLACEHOLDER_EXPIRATION_DATE = '%%EXPIRATION_DATE%%';

	/**
	 * @var \OTGS\Toolset\CRED\Controller\ExpirationManager\Post
	 */
	private $manager;

	/**
	 * @var \CRED_Notification_Manager_Post
	 */
	private $notification_manager;

	/**
	 * Manager constructor.
	 *
	 * @since 2.3
	 */
	public function __construct(
		PostExpirationManager $manager,
		\CRED_Notification_Manager_Post $notification_manager
	) {
		$this->manager = $manager;
		$this->notification_manager = $notification_manager;
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
		// Register the post expiration notification trigger
		add_action( 'toolset_forms_post_form_extend_notification_trigger', array( $this, 'add_notification_option' ), 10, 3 );
		// Notifications placeholders: resolve on notification trigger
		add_filter( 'cred_subject_notification_codes', array( $this, 'resolve_notification_placeholders' ), 10, 3 );
		add_filter( 'cred_body_notification_codes', array( $this, 'resolve_notification_placeholders' ), 10, 3 );
		// Fire custom action in the right event: 10
		// So actual posts expiration can happen at 11
		add_action( CronManager::EVENT_NAME, array( $this, 'send_scheduled_notifications' ), 10 );
	}

	/**
	 * Render the notification trigger option based on post expiration.
	 *
	 * @param \WP_Post $form
	 * @param int|string $notification_index Index for the current notification inside the notifications list.
	 * @param array $notification {
	 *     Data for the current notification.
	 *
	 *     @type array $event{
	 *         Data for the event that triggers the notification.
	 *
	 *         @type string $type Event type triggering the notification, like 'expiration_date'.
	 *         @type int $expiration_date Amount of periods to wait before triggering the post expiration.
	 *         @type string $expiration_period Period of time to collect before triggering the post expiration: minutes, hours, days, weeks.
	 *     }
	 * }
	 * @since 2.3
	 */
	public function add_notification_option( $form, $notification_index, $notification ) {
		if ( $form->post_type !== CRED_FORMS_CUSTOM_POST_NAME ) {
			return;
		}

		$context = array(
			'notification' => $notification,
			'notification_index' => $notification_index,
		);

		$template_repository = \CRED_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::NOTIFICATION_EDITOR_SECTION_POST_TRIGGER_EXPIRATION ),
			$context
		);
	}

	/**
	 * Resolve the post expiration notification placeholders when possible.
	 *
	 * @param array $placeholders
	 * @param int $form_id
	 * @param int|null $post_id
	 * @return array
	 * @since 2.3
	 */
	public function resolve_notification_placeholders( $placeholders, $form_id, $post_id ) {
		$placeholders[ self::PLACEHOLDER_EXPIRATION_DATE ] = '';
		if ( null !== $post_id ) {
			$post_expiration_time = get_post_meta( $post_id, SingularPostExpirationManager::POST_META_TIME, true );
			if ( $this->manager->get_date_utils()->is_timestamp_in_range( $post_expiration_time ) ) {
				$format = get_option( 'date_format' );
				$placeholders[ self::PLACEHOLDER_EXPIRATION_DATE ] = apply_filters( 'the_time', adodb_date( $format, $post_expiration_time ) );
			}
		}

		return $placeholders;
	}

	/**
	 * Get a list of posts that are waiting to be expired and include an expiration notification.
	 *
	 * @return array
	 * @since 2.3
	 */
	private function get_expiring_posts_with_notifications() {
		global $wpdb;
		$posts_for_notifications = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT expired_post.post_id,
						expired_post.meta_value AS expiration_time,
						post_notifications.meta_value AS notifications
					FROM $wpdb->postmeta expired_post
						INNER JOIN $wpdb->postmeta post_notifications
					ON expired_post.post_id = post_notifications.post_id
						AND expired_post.meta_key = %s
						AND post_notifications.meta_key = %s
					WHERE expired_post.meta_value != 0
						AND expired_post.meta_value IS NOT NULL",
				array(
					SingularPostExpirationManager::POST_META_TIME,
					SingularPostExpirationManager::POST_META_NOTIFICATION,
				)
			)
		);

		return $posts_for_notifications;
	}

	/**
	 * Check the existence of expiration notifications in the queue and eventually send them.
	 *
	 * @since 1.9.3 Make sure we only send expiration notifications for posts with an expiration date different than 0.
	 * @since 2.3
	 */
	public function send_scheduled_notifications() {
		$posts_for_notifications = $this->get_expiring_posts_with_notifications();

		if ( empty( $posts_for_notifications ) ) {
			return;
		}

		foreach ( $posts_for_notifications as $post_meta ) {
			$post_meta->notifications = $remaining_notifications = maybe_unserialize( $post_meta->notifications );
			$original_notifications_counter = count( $post_meta->notifications );
			// check which notification is to be activated
			foreach ( $post_meta->notifications as $key => $notification ) {
				if ( $this->maybe_send_expiration_notification( $post_meta->post_id, $post_meta->expiration_time, $notification ) ) {
					unset( $remaining_notifications[ $key ] );
				}
			}

			$force_update = ( $original_notifications_counter !== count( $remaining_notifications ) );
			$this->maybe_clean_expiration_notifications_queue( $post_meta->post_id, $remaining_notifications, $force_update );
		}
	}

	/**
	 * Maybe send a due notification on post expiration,
	 * based on the expiration time and the notification tolerance.
	 *
	 * @param int $post_id
	 * @param int $post_expiration_time Timestamp to expire the post.
	 * @param array $notification_data {
	 *     The raw notification data
	 *
	 *     @type array $event {
	 *         Data for the event that triggers the notification.
	 *
	 *         @type int $expiration_date Amount of periods to wait before triggering the post expiration.
	 *         @type string $expiration_period Period of time to collect before triggering the post expiration: minutes, hours, days, weeks.
	 *     }
	 * }
	 * @return bool Whether the notification was sent
	 * @since 2.3
	 */
	private function maybe_send_expiration_notification( $post_id, $post_expiration_time, $notification_data ) {
		$notification_model = new NotificationModel( $notification_data );

		if ( $notification_model->is_due( $post_expiration_time ) ) {
			$this->notification_manager->trigger_expiration_notifications( $post_id, $notification_model->get_form_id(), array( $notification_model->get_raw_definition() ) );

			return true;
		}

		return false;
	}

	/**
	 * Maybe clean the post expiration notifications queue
	 * from already sent notifications.
	 *
	 * @since 2.3
	 */
	private function maybe_clean_expiration_notifications_queue( $post_id, $remaining_notifications, $force_update ) {
		// update notifications list
		if ( empty( $remaining_notifications ) ) {
			delete_post_meta( $post_id, SingularPostExpirationManager::POST_META_NOTIFICATION );
		} else if ( $force_update ) {
			// We sent at least one notification, so update the list of remaining ones
			sort( $remaining_notifications );
			update_post_meta( $post_id, SingularPostExpirationManager::POST_META_NOTIFICATION, $remaining_notifications );
		}
	}

}
