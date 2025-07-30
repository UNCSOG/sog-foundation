<?php

namespace ToolsetCommonEs\Block\Style\Responsive\Devices;

use ToolsetCommonEs\Block\Style\Responsive\Devices\Themes\ITheme;
use ToolsetCommonEs\Library\WordPress\I18n;

/**
 * Class Devices
 * @package ToolsetCommonEs\Block\Style\Responsive\Devices
 */
class Devices {
	const DEVICE_DESKTOP = 'desktop';
	const DEVICE_TABLET = 'tablet';
	const DEVICE_PHONE = 'phone';
	const DEVICE_DEFAULT = self::DEVICE_DESKTOP;

	/** @var Storage */
	private $storage;

	/** @var ITheme[]  */
	private $themes = [];

	/** @var I18n */
	private $wp_i18n;

	/**
	 * @return array
	 */
	private function get_default_devices() {
		return [
			self::DEVICE_DESKTOP => [
				'label' => __( 'Desktop', 'wpv-views' ),
				'icon' => 'desktop',
				// Allows to adjust the width of the icon container.
				// Because the icons of tablet / phone are more narrow than the desktop one.
				'iconContainerWidthPercentage' => 1,
			],
			self::DEVICE_TABLET => [
				'label' => __( 'Tablet', 'wpv-views' ),
				'icon' => 'tablet',
				'iconContainerWidthPercentage' => 0.88,
				'maxWidth' => 781,
				'defaultMaxWidth' => 781,
				'columnsPerRow' => 2
			],
			self::DEVICE_PHONE => [
				'label' => __( 'Phone', 'wpv-views' ),
				'icon' => 'smartphone',
				'iconContainerWidthPercentage' => 0.8,
				'maxWidth' => 599,
				'defaultMaxWidth' => 599,
				'columnsPerRow' => 1
			],
		];
	}

	/**
	 * Devices constructor.
	 *
	 * @param Storage $storage
	 * @param I18n $wp_i18n
	 */
	public function __construct( Storage $storage, I18n $wp_i18n ) {
		$this->storage = $storage;
		$this->wp_i18n = $wp_i18n;
	}

	/**
	 * @param ITheme $theme
	 */
	public function add_theme( ITheme $theme ) {
		$this->themes[] = $theme;
	}

	/**
	 * @return array
	 */
	public function get() {
		$default_devices = $this->get_default_devices();
		$theme_devices = $this->get_theme_devices();

		$system_devices = toolset_array_merge_recursive_distinct(
			$default_devices,
			$theme_devices
		);

		$user_defined_devices = $this->storage->get();

		return toolset_array_merge_recursive_distinct( $system_devices, $user_defined_devices );
	}

	/**
	 * Returns the the list of devices keys
	 *
	 * @return array
	 */
	static public function get_list_devices() {
		return array( self::DEVICE_DESKTOP, self::DEVICE_TABLET, self::DEVICE_PHONE );
	}

	public function set( $device, $property, $value ) {
		$user_defined_devices = $this->storage->get();

		if( empty( $value ) ) {
			if( isset( $user_defined_devices[ $device ] ) && isset( $user_defined_devices[ $device ][ $property ] ) ) {
				unset( $user_defined_devices[ $device ][ $property ] );
			}
			$this->storage->save( $user_defined_devices );
			return;
		}

		$user_defined_devices[ $device ][ $property ] = $value;
		$this->storage->save( $user_defined_devices );
	}

	/**
	 * @return array
	 */
	private function get_theme_devices() {
		foreach( $this->themes as $theme ) {
			if( $theme->is_active() ) {
				return $theme->get_devices();
			}
		}

		return [];
	}
}
