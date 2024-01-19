<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes\Attributes;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes\IAttribute;

/**
 * Class ShortcodeSlug
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class ShortcodeSlug {

	/** @var string */
	private $slug;

	/**
	 * WpvControl constructor.
	 *
	 * @param string $slug
	 */
	public function __construct( $slug ) {
		if( ! is_string( $slug ) ) {
			throw new \InvalidArgumentException( 'Shortcode slug must be a string.');
		}

		if( ! preg_match( '#^[a-z\-]*$#i', $slug ) ) {
			throw new \InvalidArgumentException( 'Shortcode slug contains invalid characters.' );
		}

		$this->slug = $slug;
	}

	public function get() {
		return $this->slug;
	}
}
