<?php

namespace OTGS\Toolset\CRED\Controller\ExpirationManager\Post;

use OTGS\Toolset\CRED\Controller\ExpirationManager\Post as PostExpirationManager;

/**
 * Controller for general settings on post expiration.
 *
 * @since 2.3
 */
class Settings {

	const OPTION_NAME = 'cred_post_expiration_settings';

	/**
	 * @var \OTGS\Toolset\CRED\Controller\ExpirationManager\Post
	 */
	private $manager;

	/**
	 * Manager constructor.
	 *
	 * @since 2.3
	 */
	public function __construct( PostExpirationManager $manager ) {
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
		// Defaults
		add_filter( 'cred_ext_general_settings_options', array( $this, 'set_default_setting' ) );
		// API hooks
		add_filter( 'toolset_forms_get_post_expiration_settings', array( $this, 'get_settings' ) );
		add_action( 'toolset_forms_set_post_expiration_settings', array( $this, 'set_settings' ) );
		add_action( 'toolset_forms_remove_post_expiration_settings', array( $this, 'remove_settings' ) );
		// IMHO this should be enabled by default for all post types
		// instead of only for post types that have a form with enabled post expiration
		// and submitted at least once!?
		add_action( 'cred_pe_general_settings', array( $this, 'render_settings' ) );
		add_filter( 'cred_pe_general_settings_save', array( $this, 'save_settings' ), 10, 2 );
	}

	/**
	 * Set a default value for the general settings about enabling this feature.
	 *
	 * Not needed when using the Settings model;
	 * keep as the filter is still used in severa places, like import workflows.
	 *
	 * @param array $defaults
	 * @return array
	 * @since 2.3
	 */
	public function set_default_setting( $defaults ) {
		$defaults['enable_post_expiration'] = 0;
		return $defaults;
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
		return get_option( self::OPTION_NAME, array() );
	}

	/**
	 * Update the general post expiration settings.
	 *
	 * @param array $settings
	 * @since 2.3
	 */
	public function set_settings( $settings ) {
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Remove the general post expiration settings.
	 *
	 * @since 2.3
	 */
	public function remove_settings() {
		delete_option( self::OPTION_NAME );
	}

	/**
	 * Render the general switcher and schedule setting for the post expiration feature.
	 *
	 * @param array $settings
	 * @since 2.3
	 */
	public function render_settings( $settings ) {
		$template_repository = \CRED_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();
		$context = array(
			'enabled' => $this->manager->is_feature_enabled(),
			'schedules' => wp_get_schedules(),
			'settings' => $this->get_settings(),
		);

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::TOOLSET_SETTINGS_POST_EXPIRATION ),
			$context
		);
	}

	/**
	 * Save the general settings related to post expiration.
	 *
	 * @param array $settings
	 * @param array $posted_settings
	 * @return array
	 * @since 2.3
	 */
	public function save_settings( $settings, $posted_settings ) {

		$settings['enable_post_expiration'] = toolset_getarr( $posted_settings, 'cred_enable_post_expiration', 0 );

		$pe_settings = $this->get_settings();
		$pe_settings['post_expiration_cron']['schedule'] = toolset_getarr( $posted_settings, 'cred_post_expiration_cron_schedule', '' );
		$this->set_settings( $pe_settings );

		return $settings;
	}

}
