<?php

namespace OTGS\Toolset\CRED\Controller\DraftsManager\Post;

use OTGS\Toolset\CRED\Controller\DraftsManager\Post as PostDraftsManager;
use OTGS\Toolset\CRED\Model\Settings as SettingsModel;

/**
 * Controller for general settings on post expiration.
 */
class Settings {

	const NONCE_ID     = 'cred-post-drafts-settings';
	const SECTION_SLUG = 'forms-drafts';

	/** @var SettingsModel */
	public $settings_model;

	/** @var \Toolset_Constants */
	private $toolset_constants = null;

	/**
	 * Manager constructor
	 */
	public function __construct(
		SettingsModel $settings_model,
		\Toolset_Constants $toolset_constants
	) {
		
		$this->settings_model    = $settings_model;
		$this->toolset_constants = $toolset_constants;
	}

	/**
	 * Default schedules
	 *
	 * @return array
	 */
	private function get_default_schedules() {
		return array(
			PostDraftsManager::SETTING_DISABLE_SCHEDULE => array(
				'name'                => __( 'Never - use the WordPress automatic cleanup', 'wp-cred' ),
				'value'               => PostDraftsManager::SETTING_DISABLE_SCHEDULE,
				'schedule_in_seconds' => 0,

			),
			PostDraftsManager::SETTING_DEFAULT_SCHEDULE => array(
				'name'                => __( 'Every day', 'wp-cred' ),
				'value'               => PostDraftsManager::SETTING_DEFAULT_SCHEDULE,
				'schedule_in_seconds' => PostDraftsManager::SETTING_DEFAULT_SCHEDULE_IN_SECONDS,

			),
			PostDraftsManager::SETTING_LEGACY_SCHEDULE  => array(
				'name'                => __( 'Every 12 hours', 'wp-cred' ),
				'value'               => PostDraftsManager::SETTING_LEGACY_SCHEDULE,
				'schedule_in_seconds' => PostDraftsManager::SETTING_LEGACY_SCHEDULE_IN_SECONDS,

			),
			'every6hours'                               => array(
				'name'                => __( 'Every 6 hours', 'wp-cred' ),
				'value'               => 'every6hours',
				'schedule_in_seconds' => 6 * HOUR_IN_SECONDS,

			),
		);
	}

	/**
	 * Default threshold
	 *
	 * @return array
	 */
	private function get_default_thresholds() {
		return array(
			'week' => array(
				'name' => __( '7 days', 'wp-cred' ),
				'value' => PostDraftsManager::SETTING_DEFAULT_THRESHOLD,
			),
			'day' => array(
				'name' => __( '1 day', 'wp-cred' ),
				'value' => '-1 day',
			),
			'halfday' => array(
				'name' => __( '12 hours', 'wp-cred' ),
				'value' => '-12 hours',
			),
			'quarterday' => array(
				'name' => __( '6 hours', 'wp-cred' ),
				'value' => PostDraftsManager::SETTING_LEGACY_THRESHOLD,
			),
		);
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
		add_filter( 'cred_ext_general_settings_options', array( $this, 'set_default_setting' ) );
		add_filter( 'toolset_filter_toolset_register_settings_forms_section', array( $this, 'register_section' ), 70 );
		add_action( 'wp_ajax_cred_save_post_drafts_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Set default settings
	 *
	 * @param array $defaults
	 * @return array
	 */
	public function set_default_setting( $defaults ) {
		$defaults[ PostDraftsManager::SETTING_KEY ] = array(
			'schedule' => PostDraftsManager::SETTING_DEFAULT_SCHEDULE,
			'threshold' => PostDraftsManager::SETTING_DEFAULT_THRESHOLD,
		);
		return $defaults;
	}

	/**
	 * Validate a schedule
	 *
	 * @param string $schedule
	 * @return string
	 */
	private function validate_schedule( $schedule ) {
		$default_schedules = $this->get_default_schedules();
		return array_key_exists( $schedule, $default_schedules )
			? $schedule
			: PostDraftsManager::SETTING_DEFAULT_SCHEDULE;
	}

	/**
	 * Validate a threshold
	 *
	 * @param string $threshold
	 * @return string
	 */
	private function validate_threshold( $threshold ) {
		$default_thresholds = $this->get_default_thresholds();
		foreach ( $default_thresholds as $valid_threshold ) {
			if ( $threshold === toolset_getarr( $valid_threshold, 'value' ) ) {
				return $threshold;
			}
		}
		return PostDraftsManager::SETTING_DEFAULT_THRESHOLD;
	}

	/**
	 * Get secured stored settings
	 *
	 * @return array
	 */
	public function get_settings() {
		$stored_settings = $this->settings_model->get_setting( PostDraftsManager::SETTING_KEY );
		return array(
			'schedule'  => $this->validate_schedule(
				toolset_getarr(
					$stored_settings,
					'schedule',
					PostDraftsManager::SETTING_DEFAULT_SCHEDULE
				)
			),
			'threshold' => $this->validate_threshold(
				toolset_getarr(
					$stored_settings,
					'threshold',
					PostDraftsManager::SETTING_DEFAULT_THRESHOLD
				)
			),
		);
	}

	/**
	 * Get secured extended settings
	 *
	 * This one includes the schedule in seconds
	 *
	 * @return array
	 */
	public function get_extended_settings() {
		$default_schedules = $this->get_default_schedules();
		$stored_settings   = $this->settings_model->get_setting( PostDraftsManager::SETTING_KEY );
		$stored_schedule   = $this->validate_schedule(
			toolset_getarr(
				$stored_settings,
				'schedule',
				PostDraftsManager::SETTING_DEFAULT_SCHEDULE
			)
			);
		return array(
			'schedule'            => $stored_schedule,
			'schedule_in_seconds' => toolset_getnest( $default_schedules, array( $stored_schedule, 'schedule_in_seconds' ), PostDraftsManager::SETTING_DEFAULT_SCHEDULE_IN_SECONDS ),
			'threshold'           => $this->validate_threshold(
				toolset_getarr(
					$stored_settings,
					'threshold',
					PostDraftsManager::SETTING_DEFAULT_THRESHOLD
				)
			),
		);
	}

	/**
	 * Update the settings on the database
	 *
	 * @param array $settings
	 * @return bool
	 */
	private function set_settings( $new_settings ) {
		$settings = $this->get_settings();

		$schedule  = toolset_getarr( $new_settings, 'schedule', '' );
		$threshold = toolset_getarr( $new_settings, 'threshold', '' );

		$settings['schedule']  = $this->validate_schedule( $schedule );
		$settings['threshold'] = $this->validate_threshold( $threshold );

		return $this->settings_model->set_setting( PostDraftsManager::SETTING_KEY, $settings );
	}

	/**
	 * Register the setting section in the GUI
	 *
	 * @param array $sections
	 * @return array
	 */
	public function register_section( $sections ) {
		$sections[ self::SECTION_SLUG ] = array(
			'slug'     => self::SECTION_SLUG,
			'title'    => __( 'Post drafts', 'wp-cred' ),
			'callback' => array( $this, 'render_settings' ),
		);

		return $sections;
	}

	/**
	 * Render the GUI
	 *
	 * @codeCoverageIgnore
	 */
	public function render_settings() {
		$is_halted           = $this->toolset_constants->defined( PostDraftsManager::SHORTCIRCUIT_CONSTANT ) && true === $this->toolset_constants->constant( PostDraftsManager::SHORTCIRCUIT_CONSTANT );
		$schedules           = $this->get_default_schedules();
		$thresholds          = $this->get_default_thresholds();
		$settings            = $this->get_settings();
		$template_repository = \CRED_Output_Template_Repository::get_instance();
		$renderer            = \Toolset_Renderer::get_instance();
		$context             = array(
			'is_halted'  => $is_halted,
			'constant'   => PostDraftsManager::SHORTCIRCUIT_CONSTANT,
			'nonce_id'   => self::NONCE_ID,
			'schedule'   => toolset_getarr( $settings, 'schedule', PostDraftsManager::SETTING_DEFAULT_SCHEDULE ),
			'threshold'  => toolset_getarr( $settings, 'threshold', PostDraftsManager::SETTING_DEFAULT_THRESHOLD ),
			'schedules'  => $schedules,
			'thresholds' => $thresholds,
		);

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::TOOLSET_SETTINGS_POST_DRAFTS ),
			$context
		);
	}

	/**
	 * Save the settings
	 */
	public function save_settings( ) {
		if ( ! current_user_can('manage_options') ) {
			$data = array(
				'type'    => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wp-cred' )
			);
			wp_send_json_error( $data );
			return;
		}
		if (
			! wp_verify_nonce(
				toolset_getpost( 'wpnonce', '' ),
				self::NONCE_ID
			)
		) {
			$data = array(
				'type'    => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wp-cred' )
			);
			wp_send_json_error( $data );
			return;
		}

		$posted_string_settings = toolset_getpost( 'settings', '' );

		if ( empty( $posted_string_settings ) ) {
			$data = array(
				'type'    => 'missing',
				'message' => __( 'No data to save.', 'wp-cred' )
			);
			wp_send_json_error( $data );
			return;
		}

		parse_str( $posted_string_settings, $posted_settings );

		$settings   = $this->get_settings();
		$schedules  = $this->get_default_schedules();
		$thresholds = $this->get_default_thresholds();

		$schedule         = toolset_getarr( $posted_settings, 'cred_post_drafts_cron_schedule', '' );
		$schedule_to_save = toolset_getarr( $schedules, $schedule, false );
		if ( false !== $schedule_to_save ) {
			$settings['schedule'] = $schedule_to_save['value'];
		}

		$threshold         = toolset_getarr( $posted_settings, 'cred_post_drafts_cron_threshold', '' );
		$threshold_to_save = toolset_getarr( $thresholds, $threshold, false );
		if ( false !== $threshold_to_save ) {
			$settings['threshold'] = $threshold_to_save['value'];
		}

		if ( false === $schedule_to_save && false === $threshold_to_save ) {
			$data = array(
				'type'    => 'invalid',
				'message' => __( 'No valid data to save.', 'wp-cred' )
			);
			wp_send_json_error( $data );
			return;
		}

		$this->set_settings( $settings );
		wp_send_json_success();
	}

}
