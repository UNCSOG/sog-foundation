<?php

namespace ToolsetCommonEs\Utils;

use ToolsetCommonEs\Library\WordPress\Option;

/**
 * Class SettingsStorage
 *
 * Handles storage of Toolset Blocks settings.
 *
 * @package ToolsetCommonEs\Utils
 */
class SettingsStorage {
	// This was originaly part of TB - keep the key like it is to prevent data loss.
	const WP_OPTION_KEY = 'toolset_blocks_settings';

	const SETTINGS_ADD_BLOCK_ID_TO_SELECTORS = 'add-block-id-to-selectors';

	/** @var array */
	private $settings = array();

	/** @var Option */
	private $wp_option;

	/**
	 * SettingsStorage constructor.
	 *
	 * @param Option $wp_option
	 */
	public function __construct( Option $wp_option ) {
		$this->wp_option = $wp_option;
		$this->settings = $wp_option->get_option( self::WP_OPTION_KEY, array() );
	}

	/**
	 * Update a setting.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function update_setting( $key, $value ) {
		$this->settings[ $key ] = $value;

		$this->wp_option->update_option( self::WP_OPTION_KEY, $this->settings );
	}

	/**
	 * Get setting.
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return bool|mixed
	 */
	public function get_setting( $key, $default = false ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
	}
}
