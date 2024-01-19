<?php

namespace OTGS\Toolset\CRED\Controller\ExpirationManager;

use OTGS\Toolset\CRED\Model\Settings;
use OTGS\Toolset\CRED\Model\Wordpress\Status;

/**
 * Post expiration workflow controller.
 *
 * @since 2.3
 */
class Post {

	/**
	 * @var Settings
	 */
	private $settings_model;

	/**
	 * @var \Toolset_Date_Utils
	 */
	private $date_utils;

	/**
	 * @var Status
	 */
	private $status_model;

	/**
	 * @var bool
	 */
	private $feature_enabled = null;

	/**
	 * @var Post\Settings
	 */
	private $settings_manager = null;

	/**
	 * Manager constructor.
	 *
	 * @since 2.3
	 */
	public function __construct(
		Settings $settings_model,
		\Toolset_Date_Utils $date_utils,
		Status $status_model
	) {
		
		$this->settings_model = $settings_model;
		$this->date_utils = $date_utils;
		$this->status_model = $status_model;
	}

	/**
	 * Get the manager settings model instance.
	 *
	 * @return Settings
	 * @since 2.3
	 */
	public function get_settings_model() {
		return $this->settings_model;
	}

	/**
	 * Get the manager date utils instance.
	 *
	 * @return \Toolset_Date_Utils
	 * @since 2.3
	 */
	public function get_date_utils() {
		return $this->date_utils;
	}

	/**
	 * Get the status model instance.
	 *
	 * @return \Status
	 * @since 2.3
	 */
	public function get_status_model() {
		return $this->status_model;
	}

	/**
	 * Initialize the manager.
	 *
	 * @since 2.3
	 */
	public function initialize() {
		$this->add_hooks();

		// Initialize the settings component
		$this->settings_manager = new Post\Settings( $this );
		$this->settings_manager->initialize();

		if ( ! $this->is_feature_enabled() ) {
			// Clear the cron component schedule
			$cron_manager = new Post\Cron( $this );
			$cron_manager->clear_schedule();
			return;
		}

		// Initialize the post editor component
		$singular_manager = new Post\Singular( $this );
		$singular_manager->initialize();
		// Initialize the form editor component
		$form_manager = new Post\Form( $this );
		$form_manager->initialize();
		// Initialize the cron component
		$cron_manager = new Post\Cron( $this );
		$cron_manager->initialize();
		// Initialize the action component
		$action_manager = new Post\Action( $this );
		$action_manager->initialize();
		// Initialize the notifications component
		$notifications_manager = new Post\Notifications( $this, \CRED_Notification_Manager_Post::get_instance() );
		$notifications_manager->initialize();
	}

	/**
	 * Add hooks, including API hooks.
	 *
	 * @since 2.3
	 */
	private function add_hooks() {
		// API hooks
		add_filter( 'toolset_forms_is_post_expiration_enabled', array( $this, 'is_feature_enabled' ) );
	}

	/**
	 * Check whether the post expiration feature is enabled in the general settings.
	 *
	 * @return bool
	 * @since 2.3
	 */
	public function is_feature_enabled() {
		if ( null !== $this->feature_enabled ) {
			return $this->feature_enabled;
		}
		$settings = $this->settings_model->get_settings();
		$this->feature_enabled = (bool) toolset_getarr( $settings, 'enable_post_expiration', false );
		return $this->feature_enabled;
	}

	/**
	 * Get the general post expiration settings, including:
	 * - cron schedule for checking for expired posts.
	 * - post types that should have post expiration enabled.
	 *
	 * @return array
	 * @since 2.3
	 */
	public function get_settings() {
		return ( null === $this->settings_manager )
			? array()
			: $this->settings_manager->get_settings();
	}

	/**
	 * Enable the post expiration feature on a given post type.
	 *
	 * @param string $post_type
	 * @since 2.3
	 */
	public function add_post_type_support( $post_type ) {
		$settings = $this->get_settings();

		if ( ! isset( $settings['post_expiration_post_types'] ) ) {
			$settings['post_expiration_post_types'] = array();
		}

		if ( ! in_array( $post_type, $settings['post_expiration_post_types'] ) ) {
			$settings['post_expiration_post_types'][] = $post_type;
			do_action( 'toolset_forms_set_post_expiration_settings', $settings );
		}
	}

	/**
	 * Merge recursive arrays.
	 * Copy values from $array2 over values from $array1.
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 */
	public function array_merge_distinct( $array1, &$array2 ) {
		$merged = $array1;
		foreach ( $array2 as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
				$merged[ $key ] = $this->array_merge_distinct( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

}
