<?php

namespace OTGS\Toolset\CRED\Model;

use OTGS\Toolset\CRED\Controller\DraftsManager\Post as PostDraftsManager;

/**
 * General settings model.
 *
 * @since 2.1
 */
class Settings {

	const OPTION_NAME = 'cred_cred_settings';

	/**
	 * @var OTGS\Toolset\CRED\Model\Settings
	 *
	 * @since 2.1
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	private $defaults = array(
		'wizard' => 1,
		'cache_notice' => 1,
		'export_settings' => 1,
		'export_custom_fields' => 1,
		'recaptcha' => array(
			'public_key' => '',
			'private_key' => ''
		),
		'autogeneration_email' => array(
			'subject' => 'Welcome new user',
			'body' => '[username]Your username is: %cuf_username%[/username]\\n[nickname]Your nickname is: %cuf_nickname%[/nickname]\\n[password]Your password is: %cuf_password%[/password]'
		),
		'dont_load_cred_css' => 1,
		'enable_post_expiration' => 0,
		PostDraftsManager::SETTING_KEY => array(
			'schedule' => PostDraftsManager::SETTING_LEGACY_SCHEDULE,
			'threshold' => PostDraftsManager::SETTING_LEGACY_THRESHOLD,
		),
	);

	/**
	 * Get an instance of this object.
	 *
	 * @return OTGS\Toolset\CRED\Model\Settings
	 * @since 2.1
	 */
	public static function get_instance() {
		if ( null == Settings::$instance ) {
			Settings::$instance = new Settings();
		}
		return Settings::$instance;
	}

	/**
	 * Clear the current instance.
	 *
	 * @since 2.1
	 */
	public static function clear_instance() {
		if ( Settings::$instance ) {
			Settings::$instance = null;
		}
	}

	/**
	 * Get the stored settings.
	 *
	 * @return array
	 * @since 2.2.1
	 */
	public function get_raw_stored_settings() {
		return get_option( self::OPTION_NAME, array() );
	}

	/**
	 * Get the stored settings with defaults applied.
	 *
	 * @return array
	 * @since 2.1
	 */
	public function get_settings() {
		$settings = $this->get_raw_stored_settings();

		foreach ( $this->defaults as $default_key => $default_value ) {
			if ( ! array_key_exists( $default_key, $settings ) ) {
				$settings[ $default_key ] = $default_value;
			}
		}

		return $settings;
	}

	/**
	 * Get an individual setting.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get_setting( $key ) {
		$settings = $this->get_settings();
		return toolset_getarr( $settings, $key, false );
	}

	/**
	 * Save settings.
	 *
	 * @param array $settings
	 * @return boolean
	 * @since 2.1
	 */
	public function set_settings( $settings ) {
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Set an individual setting.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return boolean
	 * @since 2.1
	 */
	public function set_setting( $key, $value ) {
		$settings = $this->get_settings();
		$settings[ $key ] = $value;
		return $this->set_settings( $settings );
	}

	/**
	 * Set the initial settings when there is no stored set.
	 *
	 * @since 2.1
	 */
	public function set_initial_settings() {
		$settings = $this->get_raw_stored_settings();

		if ( ! empty( $settings ) ) {
			return;
		}

		// Extend the default settings.
		$this->defaults = apply_filters( 'cred_ext_general_settings_options', $this->defaults );

		$this->set_settings( $this->defaults );
	}

}
