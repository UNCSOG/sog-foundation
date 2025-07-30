<?php

namespace OTGS\Toolset\Layouts\Util;

use OTGS\Toolset\Common\Settings\BootstrapSetting;
use Toolset_Settings;

/**
 * An attempt to bring sanity into column size prefixes for both supported versions of Bootstrap.
 *
 * @package OTGS\Toolset\Layouts\Util
 */
class BootstrapColumnSizes {

	const EXTRA_SMALL = 'extra_small';
	const SMALL = 'small';
	const MEDIUM = 'medium';
	const LARGE = 'large';
	const EXTRA_LARGE = 'extra_large';

	const DEFAULT_VALUE = 'default';

	const DEFAULT_BS_VERSION = BootstrapSetting::NUMERIC_BS3;

	const SIZE_TO_CLASS = [
		self::DEFAULT_BS_VERSION => [
			self::EXTRA_SMALL => 'col-xs-',
			self::SMALL => 'col-sm-',
			self::MEDIUM => 'col-md-',
			self::LARGE => 'col-lg-',
			self::DEFAULT_VALUE => 'col-sm-',
		],
		BootstrapSetting::NUMERIC_BS4 => [
			self::EXTRA_SMALL => 'col-',
			self::SMALL => 'col-sm-',
			self::MEDIUM => 'col-md-',
			self::LARGE => 'col-lg-',
			self::EXTRA_LARGE => 'col-xl-',
			self::DEFAULT_VALUE => 'col-md-'
		]
	];


	private static $instance;


	/** @var Toolset_Settings */
	private $toolset_settings;


	public function __construct( Toolset_Settings $toolset_settings = null ) {
		$this->toolset_settings = $toolset_settings ?: Toolset_Settings::get_instance();
	}


	public static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * For a given column size, return the HTML class prefix based on the currently used Bootstrap version.
	 *
	 * @param string $column_size
	 *
	 * @return string
	 */
	public function get_column_class_prefix( $column_size ) {
		$bs_version = $this->toolset_settings->get_bootstrap_version_numeric();
		if( ! array_key_exists( $bs_version, self::SIZE_TO_CLASS ) ) {
			$bs_version = self::DEFAULT_BS_VERSION;
		}

		$sizes_per_bs_version = self::SIZE_TO_CLASS[ $bs_version ];
		return toolset_getarr( $sizes_per_bs_version, $column_size, '' );
	}
}
