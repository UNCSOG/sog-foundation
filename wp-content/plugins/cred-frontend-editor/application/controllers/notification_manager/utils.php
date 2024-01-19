<?php

/**
 * Class CRED_Notification_Manager_Utils used to initialize/manage hooks
 */
class CRED_Notification_Manager_Utils {

	private static $instance;

	/**
	 * For testing injection
	 *
	 * @var CRED_Notification_Manager_Post
	 * @since 2.0.1
	 */
	private $cred_notification_post;

	/**
	 * For testing injection
	 *
	 * @var CRED_Notification_Manager_User
	 * @since 2.0.1
	 */
	private $cred_notification_user;

	/**
	 * For testing injection
	 *
	 * @var CRED_Notification_Manager_Queue
	 * @since 2.0.1
	 */
	private $cred_notification_queue;

	/**
	 * Singleton
	 *
	 * @param CRED_Notification_Manager_Post $cred_notification_post_di For testing purposes.
	 * @param CRED_Notification_Manager_User $cred_notification_user_di For testing purposes.
	 * @param CRED_Notification_Manager_Queue $cred_notification_queue_di For testing purposes.
	 * @since 2.0.1 New parameters
	 */
	public static function get_instance( CRED_Notification_Manager_Post $cred_notification_post_di = null, CRED_Notification_Manager_User $cred_notification_user_di = null, CRED_Notification_Manager_Queue $cred_notification_queue_di = null ) {
		if ( null == self::$instance ) {
			self::$instance = new self(  $cred_notification_post_di, $cred_notification_user_di, $cred_notification_queue_di );
		}

		return self::$instance;
	}

	/**
	 * CRED_Notification_Manager_Utils constructor.
	 *
	 * @param CRED_Notification_Manager_Post $cred_notification_post_di For testing purposes.
	 * @param CRED_Notification_Manager_User $cred_notification_user_di For testing purposes.
	 * @param CRED_Notification_Manager_Queue $cred_notification_queue_di For testing purposes.
	 * @since 2.0.1 New parameters
	 */
	public function __construct( CRED_Notification_Manager_Post $cred_notification_post_di = null, CRED_Notification_Manager_User $cred_notification_user_di = null, CRED_Notification_Manager_Queue $cred_notification_queue_di = null ) {
		$this->cred_notification_post = $cred_notification_post_di;
		$this->cred_notification_user = $cred_notification_user_di;
		$this->cred_notification_queue = $cred_notification_queue_di;
		$this->initialize();
	}


	/**
	 * Gets notification manager for posts
	 *
	 * @return CRED_Notification_Manager_Post
	 * @since 2.0.1
	 */
	private function get_notification_manager_post() {
		if ( $this->cred_notification_post ) {
			return $this->cred_notification_post;
		}
		return CRED_Notification_Manager_Post::get_instance();
	}


	/**
	 * Gets notification manager for users
	 *
	 * @return CRED_Notification_Manager_User
	 * @since 2.0.1
	 */
	private function get_notification_manager_user() {
		if ( $this->cred_notification_user ) {
			return $this->cred_notification_user;
		}
		return CRED_Notification_Manager_User::get_instance();
	}


	/**
	 * Gets notification manager queue
	 *
	 * @return CRED_Notification_Manager_Queue
	 * @since 2.0.1
	 */
	private function get_notification_manager_queue() {
		if ( $this->cred_notification_queue ) {
			return $this->cred_notification_queue;
		}
		return CRED_Notification_Manager_Queue::get_instance();
	}


	/**
	 * Init hooks
	 */
	public function initialize() {
		add_action( 'wp_loaded', array( $this, 'add_hooks' ), 10 );

		// API hooks
		add_action( 'toolset_forms_add_notifications_trigger_hooks', array( $this, 'add_hooks' ) );
		add_action( 'toolset_forms_remove_notifications_trigger_hooks', array( $this, 'remove_hooks' ) );
	}

	public function add_hooks() {
		

		$cred_notification_post = $this->get_notification_manager_post();

		add_action( 'save_post', array( $cred_notification_post, 'save_post_check_for_notifications' ), 10, 3 );
		add_action( 'pre_post_update', array( $cred_notification_post, 'check_for_notifications_for_title_and_content' ), 10, 2 );

		$cred_notification_user = $this->get_notification_manager_user();
		add_action( 'profile_update', array( $cred_notification_user, 'check_for_notifications' ), 10, 2 );
		// Evaluating wp user fields.
		add_action( 'added_user_meta', array( $cred_notification_user, 'check_for_notifications_for_user_meta' ), 1, 3 );

		// Fields evaluation from WP backend.
		add_action( 'pre_post_update', array( $cred_notification_post, 'save_pre_snapshot' ), 1 );
		add_action( 'personal_options_update', array( $cred_notification_user, 'save_pre_snapshot' ), 1 );
		add_action( 'edit_user_profile_update', array( $cred_notification_user, 'save_pre_snapshot' ), 1 );

		$cred_notification_queue = $this->get_notification_manager_queue();
		add_action( 'shutdown', array( $cred_notification_queue, 'send' ) );

		/**
		 * check if status is changed
		 */
		$check_to_status = array( 'publish', 'pending', 'draft', 'private' );
		$check_from_status = array_merge( $check_to_status, array( 'new', 'future', 'trash' ) );
		foreach ( $check_from_status as $from ) {
			foreach ( $check_to_status as $to ) {
				if ( $from !== $to ) {
					$action = sprintf( '%s_to_%s', $from, $to );
					add_action( $action, array(
						$cred_notification_post,
						'check_for_notifications_by_status_switch',
					), 10, 1 );
				}
			}
		}
		add_action( 'wp_trash_post', array(
			$cred_notification_post,
			'check_for_notifications_by_status_switch',
		), 10, 1 );

		foreach( [ 'updated', 'added' ] as $action ) {
			$action_name = sprintf( '%s_meta', $action );
			add_action( $action_name, array( $cred_notification_post, $action_name ), 20, 4 );
		}
	}

	public function remove_hooks() {

		$cred_notification_post = $this->get_notification_manager_post();
		remove_action( 'save_post', array( $cred_notification_post, 'check_for_notifications' ), 10, 2 );

		$cred_notification_user = $this->get_notification_manager_user();
		remove_action( 'profile_update', array( $cred_notification_user, 'check_for_notifications' ), 1, 2 );

		$check_to_status = array( 'publish', 'pending', 'draft', 'private' );
		$check_from_status = array_merge( $check_to_status, array( 'new', 'future', 'trash' ) );
		foreach ( $check_from_status as $from ) {
			foreach ( $check_to_status as $to ) {
				if ( $from !== $to ) {
					$action = sprintf( '%s_to_%s', $from, $to );
					remove_action( $action, array( $cred_notification_post, 'check_for_notifications' ), 10, 1 );
				}
			}
		}

		foreach( [ 'updated', 'added' ] as $action ) {
			$action_name = sprintf( '%s_meta', $action );
			remove_action( $action_name, array( $cred_notification_post, $action . '_meta' ), 20, 4 );
		}
	}
}
