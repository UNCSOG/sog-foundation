<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsToPackageService;


/**
 * Class AttributeProperties
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class AttributeProperties {

	/** @var string */
	private $name;

	/** @var string */
	private $title;

	/**
	 * AttributeProperties constructor.
	 *
	 * @param string $name
	 * @param string $title
	 */
	public function __construct( $name, $title ) {
		if( ! is_string( $name ) ) {
			throw new \InvalidArgumentException( '$name must be a string.');
		}

		if( ! preg_match( '#^[a-z\_]*$#i', $name ) ) {
			throw new \InvalidArgumentException( '$name contains invalid characters.' );
		}

		if( ! is_string( $title ) ) {
			throw new \InvalidArgumentException( '$title must be a string.');
		}


		$this->name  = $name;
		$this->title = $title;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_title() {
		return $this->title;
	}
}
