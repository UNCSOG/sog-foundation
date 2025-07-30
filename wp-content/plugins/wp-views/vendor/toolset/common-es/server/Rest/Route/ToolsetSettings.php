<?php

namespace ToolsetCommonEs\Rest\Route;

use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;
use ToolsetCommonEs\Library\WordPress\User;
use ToolsetCommonEs\Utils\SettingsStorage;

/**
 * Class ToolsetSettings
 *
 * This currently includes all callbacks for the Toolset Common Es settings.
 *
 * - Apply block id to selectors
 * - Responsive width breakpoints
 *
 * The related setting markup can be found in /Block/Style/ToolsetSettings.php
 */
class ToolsetSettings extends ARoute {
	/** @var string */
	protected $name = 'ToolsetSettings';

	/** @var int */
	protected $version = 1;

	/** @var Devices */
	private $devices;

	/** @var SettingsStorage */
	private $settings_storage;


	/**
	 * Settings constructor.
	 *
	 * @param User $wp_user
	 * @param Devices $devices
	 * @param SettingsStorage $settings_storage
	 */
	public function __construct( User $wp_user, Devices $devices, SettingsStorage $settings_storage ) {
		parent::__construct( $wp_user );

		$this->devices = $devices;
		$this->settings_storage = $settings_storage;
	}


	/**
	 * Callback for settings.
	 *
	 * @param \WP_REST_Request $rest_request
	 *
	 * @return array|int Array on error. 1 on success.
	 */
	public function callback( \WP_REST_Request $rest_request ) {
		$params = $rest_request->get_json_params();

		if ( ! is_array( $params ) || ! isset( $params['action'] ) ) {
			return array( 'error' => __( 'Invalid request.', 'wpv-views' ) );
		}

		switch ( $params['action'] ) {
			case 'update-devices-max-width':
				return $this->update_devices_max_width( $params );
			case 'add-block-id-to-selectors':
				return $this->add_body_id_to_selectors( $params );
		}

		return array( 'error' => __( 'Invalid request.', 'wpv-views' ) );
	}


	/**
	 * Check if the current user can change settings.
	 *
	 * @return bool
	 */
	public function permission_callback() {
		// @todo check for Toolset Access permissions
		return $this->wp_user->current_user_can( 'edit_posts' );
	}

	/**
	 * Callback for enable / disable block id to selectors option.
	 *
	 * @param array $params
	 *
	 * @return array|int Array on error. 1 on success.
	 */
	private function add_body_id_to_selectors( $params ) {
		if ( ! isset( $params['is-checked'] ) ) {
			return array( 'error' => __( 'Missing is-checked param.', 'wpv-views' ) );
		}

		// Offload to a variable to access its class constants:
		// PHP5.6 does not support $this->that::CONSTANT.
		$settings_storage_instance = $this->settings_storage;

		$this->settings_storage->update_setting(
			$settings_storage_instance::SETTINGS_ADD_BLOCK_ID_TO_SELECTORS,
			! empty( $params['is-checked'] ) ? 1 : 0
		);

		return 1;
	}

	/**
	 * Callback for updating the max width for a device.
	 *
	 * @param array $params
	 *
	 * @return array|int Array on error. 1 on success.
	 */
	private function update_devices_max_width( $params ) {
		if ( ! isset( $params['devices'] ) ) {
			return array( 'error' => __( 'Missing devices param.', 'wpv-views' ) );
		}

		$devices = $this->devices->get();
		$devices = toolset_array_merge_recursive_distinct( $devices, $params['devices'] );

		$phone_max_width = $devices['phone']['maxWidth'] ?: $devices['phone']['defaultMaxWidth'];
		$tablet_max_width = $devices['tablet']['maxWidth'] ?: $devices['tablet']['defaultMaxWidth'];

		if ( $phone_max_width >= $tablet_max_width ) {
			return array(
				'error' => __( 'Tablet width should be larger than phone width.', 'wpv-views' ),
			);
		}

		foreach ( $params['devices'] as $device_key => $device ) {
			$this->devices->set( $device_key, 'maxWidth', $device['maxWidth'] );
		}

		return 1;
	}
}
