<?php
namespace ToolsetCommonEs\Block\Style;

use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;
use ToolsetCommonEs\Utils\SettingsStorage;

/**
 * Class ToolsetSettings
 *
 * This currently handles all settings markup added by Toolset Common Es.
 *
 * - Apply block id to selectors
 * - Responsive width breakpoints
 *
 * The related ajax callbacks can be found in /Rest/Route/ToolsetSettings.php
 *
 * Note that when Views (or Blocks) is not available, this class is not instantiated: check bootstrap.php.
 */
class ToolsetSettings {
	/** @var Devices */
	private $devices;

	/** @var SettingsStorage */
	private $settings_storage;

	/**
	 * ToolsetSettings constructor.
	 *
	 * @param Devices $devices
	 * @param SettingsStorage $settings_storage
	 */
	public function __construct( Devices $devices, SettingsStorage $settings_storage ) {
		$this->devices = $devices;
		$this->settings_storage = $settings_storage;
	}

	/**
	 * @filter toolset_filter_toolset_register_settings_general_section
	 *
	 * @param array $sections
	 *
	 * @return mixed
	 */
	public function callback_toolset_filter_toolset_register_settings_general_section( $sections ) {
		// Make sure WP Rest is running.
		rest_get_server();

		wp_enqueue_script(
			'toolset-common-es-settings',
			TOOLSET_COMMON_ES_URL . 'public/toolset-common-es-settings.js',
			[ 'wp-api-fetch' ],
			TOOLSET_COMMON_ES_LOADED,
			true // Enqueue the script in the footer.
		);

		wp_localize_script(
			'toolset-common-es-settings',
			'toolsetCommonEsSettings',
			array( 'rest' => rest_url( 'wp/v2/tutorial' ) )
		);

		$sections = $this->add_block_id_to_selectors_setting( $sections );
		$sections = $this->add_responsive_setting( $sections );
		return $sections;
	}

	/**
	 * Block id to selectors setting.
	 *
	 * @param array $sections Previous settings.
	 *
	 * @return array Previous settings plus block id to selectors setting.
	 */
	private function add_block_id_to_selectors_setting( $sections ) {
		// Offload to a variable to access its class constants:
		// PHP5.6 does not support $this->that::CONSTANT.
		$settings_storage_instance = $this->settings_storage;

		$checked = ! empty(
			$this->settings_storage->get_setting( $settings_storage_instance::SETTINGS_ADD_BLOCK_ID_TO_SELECTORS )
		) ? ' checked="checked"' : '';

		$sections['add-block-id-to-selectors'] = array(
			'slug' => 'add-block-id-to-selectors',
			'title' => __( 'CSS selectors of Toolset Blocks', 'wpv-views' ),
			'content' =>
				'<h3>' . __( 'More specific CSS selectors', 'wpv-views' ) . '</h3>' .
				'<div class="tces-settings-add-block-id-to-selectors-error" style="display:none;">' .
				'<p class="notice notice-error notice-alt"></p>' .
				'</div>' .
				'<div class="toolset-advanced-setting">' .
					'<p>' .
						__( 'Activating this option makes it less likely for your theme to overwrite the block styling.', 'wpv-views' ) .
					'</p>' .
					'<p>' .
						'<label>' .
							'<input class="js-wpv-add-block-id-to-selectors" type="checkbox"' . $checked . ' />' .
							__( 'Add an ID to the <b>body</b> tag for use by Blocks CSS selectors, or use an existing <b>body</b> ID when available.', 'wpv-views' ) .
						'</label>' .
					'</p>' .
				'</div>',
		);

		return $sections;
	}


	/**
	 * Responsive settings section.
	 *
	 * @param array $sections Previous settings.
	 *
	 * @return array Previous settings plus responsive settings section.
	 */
	private function add_responsive_setting( $sections ) {
		$devices = $this->devices->get();

		// Sort devices by default max width. Lowest first.
		uasort( $devices, function( $a, $b ) {
			// PHP INT MAX if there is no default max width (desktop).
			$a_max_width = isset( $a['defaultMaxWidth'] ) ? $a['defaultMaxWidth'] : PHP_INT_MAX;
			$b_max_width = isset( $b['defaultMaxWidth'] ) ? $b['defaultMaxWidth'] : PHP_INT_MAX;

			return ( $a_max_width > $b_max_width ) ? 1 : -1;
		} );

		$section_content = '<div class="tces-settings-rwd-devices">';
		$zindex = 21;

		foreach ( $devices as $device_key => $device_info ) {
			$zindex--;
			$section_content .= '<div class="tces-settings-rwd-device" style="z-index: ' . $zindex . ';">';

			$value = array_key_exists( 'maxWidth', $device_info ) &&
			array_key_exists( 'defaultMaxWidth', $device_info ) &&
			$device_info['maxWidth'] !== $device_info['defaultMaxWidth'] ?
				$device_info['maxWidth'] :
				'';

			$section_content .= '<h3 style="margin-bottom: -5px;"><span class="dashicons dashicons-' .
				$device_info['icon'] . '"></span>' . $device_info['label'] . '</h3>';

			if ( $device_key !== Devices::DEVICE_DESKTOP ) {
				$section_content .= '<div class="tces-settings-rwd-device-input">' .
					'<input class="js-wpv-rwd-device" type="number" min="1" max="2000" ' .
					'data-device-key="' . $device_key . '" name="devices[' . $device_key . '][maxWidth]" ' .
					'placeholder="' . $device_info['defaultMaxWidth'] . '" ' .
					'class="js-wpv-editing-experience-option" value="' . $value . '" /> px' .
					'</div>';
			}
			$section_content .= '</div>';
		}

		$section_content .= '</div>';

		$section_content .= '<div class="tces-settings-rwd-error">' .
			'<span class="notice notice-error notice-alt"></span>' .
			'</div>';

		$section_content .= '<p class="description wpcf-form-description tces-settings-rwd-description"> ' .
			__( '* By default the WordPress Columns breakpoints are used: ', 'wpv-views' ) .
			'<span class="dashicons dashicons-' . $devices[ Devices::DEVICE_PHONE ]['icon'] . '"></span>' .
			$devices[ Devices::DEVICE_PHONE ]['defaultMaxWidth'] . ' px ' .
			'<span class="dashicons dashicons-' . $devices[ Devices::DEVICE_TABLET ]['icon'] . '"></span>' .
			$devices[ Devices::DEVICE_TABLET ]['defaultMaxWidth'] . ' px' .
			'</p>';

		$sections['responsive-breakpoints'] = array(
			'slug' => 'responsive-breakpoints',
			'title' => __( 'Responsive web design breakpoints for Toolset Blocks', 'wpv-views' ),
			'content' => $section_content,
		);

		return $sections;
	}
}
