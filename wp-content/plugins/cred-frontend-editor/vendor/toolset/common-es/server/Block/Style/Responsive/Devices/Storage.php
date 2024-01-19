<?php

namespace ToolsetCommonEs\Block\Style\Responsive\Devices;

use ToolsetCommonEs\Library\WordPress\Option;

/**
 * Class Storage
 * @package ToolsetCommonEs\Block\Style\Responsive
 */
class Storage {
	const OPTION_KEY = 'toolset_responsive_web_design';

	/** @var Option */
	private $wp_option;

	/**
	 * Storage constructor.
	 *
	 * @param Option $wp_option
	 */
	public function __construct( Option $wp_option ) {
		$this->wp_option = $wp_option;
	}

	/**
	 * Get stored value.
	 * @return array
	 */
	public function get() {
		$value = $this->wp_option->get_option( self::OPTION_KEY, [] );

		return is_array( $value ) ? $value : [];
	}

	/**
	 * Save new value.
	 *
	 * @param mixed[] $user_devices
	 */
	public function save( $user_devices ) {
		$user_devices = is_array( $user_devices ) ? $user_devices : [];

		foreach ( $user_devices as &$device ) {
			if( array_key_exists( 'maxWidth', $device ) ) {
				$device['maxWidth'] = (int) $device['maxWidth'];
			}
		}

		$this->wp_option->update_option( self::OPTION_KEY, $user_devices );
	}
}
