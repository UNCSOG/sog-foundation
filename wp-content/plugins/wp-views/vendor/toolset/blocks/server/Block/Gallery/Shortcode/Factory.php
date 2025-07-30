<?php

namespace ToolsetBlocks\Block\Gallery\Shortcode;

use ToolsetBlocks\Block\Image\MediaLibrary;
use ToolsetCommonEs\Library\WordPress\Shortcode;

/**
 * Adds Gallery Shortcodes
 *
 * @since 1.2
 */
class Factory {
	/** @var MediaLibrary */
	private $media_library;

	/** @var Shortcode */
	private $wp_shortcode;


	/**
	 * Factory constructor.
	 *
	 * @param MediaLibrary $media_library
	 * @param Shortcode $wp_shortcode
	 */
	public function __construct( MediaLibrary $media_library, Shortcode $wp_shortcode ) {
		$this->media_library = $media_library;
		$this->wp_shortcode = $wp_shortcode;
	}

	/**
	 * Initializes the class
	 */
	public function initialize() {
		$this->add_shortcodes();
	}

	/**
	 * Adds the shortcodes
	 */
	private function add_shortcodes() {
		( new Caption( $this->media_library ) )->initialize();
		( new AltText( $this->media_library, $this->wp_shortcode ) )->initialize();
	}
}
