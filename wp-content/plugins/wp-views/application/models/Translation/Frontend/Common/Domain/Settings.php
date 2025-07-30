<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain;

/**
 * Class WPASettings
 *
 * Settings of the View / WPA.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain
 *
 * @since TB 1.3
 */
class Settings {

	/** @var array */
	private $settings;

	/**
	 * Settings constructor.
	 *
	 * @param $settings
	 */
	public function __construct( $settings ) {
		if( ! is_array( $settings ) || ! array_key_exists( 'filter_meta_html', $settings ) ) {
			throw new \InvalidArgumentException( 'Given $settings does not contain filter_meta_html key.' );
		}
		$this->settings = $settings;

		// Validate filter_meta_html value.
		$this->set_filter_meta_html( $settings['filter_meta_html'] );
	}

	public function get() {
		return $this->settings;
	}

	public function set_filter_meta_html( $string ) {
		if( ! is_string( $string ) ) {
			throw new \InvalidArgumentException( "set_filter_meta_html only accept strings." );
		}

		$this->settings['filter_meta_html'] = $string;
	}
}
