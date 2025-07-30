<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain;

/**
 * Class Block
 *
 * Value object just to store the block name and content lines.
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain
 *
 * @since TB 1.3
 */
class Block {
	/** @var string */
	private $name;

	/** @var ContentLines */
	private $content_lines;

	/**
	 * AEntity constructor.
	 *
	 * @param string $block_name
	 * @param ContentLines $content
	 */
	public function __construct( $block_name, ContentLines $content ) {
		if( ! is_string( $block_name ) ) {
			throw new \InvalidArgumentException( '$block_name must be a string.' );
		}
		$this->name = $block_name;
		$this->content_lines = $content;
	}

	/**
	 * @return string
	 */
	public function name() {
		return $this->name;
	}

	/**
	 * @return ContentLines
	 */
	public function content_lines() {
		return $this->content_lines;
	}
}
