<?php

/**
 * Enqueue notifications for later sending.
 * Notifications are indentified by Form ID, (post/user) ID and event type (form submit, ...).
 * The system will remove duplicated notifications and cancel by event type.
 *
 * @since 2.0.1
 */
class CRED_Notification_Manager_Queue {
	/**
	 * Singleton object
	 *
	 * @var CRED_Notification_Manager_Queue
	 * @since 2.0.1
	 */
	private static $instance;

	/**
	 * Notifications queue. Group by event type so they can easily cancelled.
	 *
	 * @var array
	 * @since 2.0.1
	 */
	private $queue = array();


	/**
	 * Singleton pattern
	 *
	 * @return CRED_Notification_Manager_Queue
	 * @since 2.0.1
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Enqueue notifications
	 *
	 * Notifications are grouped by event type so they can be removed easily.
	 *
	 * @param int Post or User ID.
	 * @param int Form ID.
	 * @param string $event_type Event type (form submit, ...)
	 * @param string $class_name Notification manager class name.
	 * @param array $notification Notification to send.
	 * @param array $snapshot_fields Snapshot fields.
	 */
	public function enqueue( $object_id, $form_id, $event_type = 'undefined', $class_name = '', $notification = array() ) {
		if ( ! isset( $this->queue[ $event_type ] ) ) {
			$this->queue[ $event_type ] = array();
		}
		$notification_id = $object_id . '-' . $form_id . '-' . $notification['name'] . '-' . $event_type;
		if ( ! isset( $this->queue[ $event_type ][ $notification_id ] ) ) {
			$this->queue[ $event_type ][ $notification_id ] = array(
				'class' => $class_name,
				'object_id' => $object_id,
				'form_id' => $form_id,
				'notification' => $notification,
			);
		}
	}


	/**
	 * Send notifications
	 *
	 * @since 2.0.1
	 */
	public function send() {
		foreach ( $this->queue as $event_type => $notifications ) {
			foreach ( $notifications as $id => $notification ) {
				$manager = call_user_func( array( $notification['class'], 'get_instance' ) );
				$manager->send_notifications( $notification['object_id'], $notification['form_id'], array( $notification['notification'] ) );
				unset( $this->queue[ $event_type ][ $id ] );
			}
		}
	}
}
