<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\SearchPosition;

/**
 * Class Factory
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\SearchPosition
 */
class Factory {
	/** @var array[string]Block */
	private $blocks = [];


	/**
	 * Creates and returns singletons of blocks.
	 *
	 * @param $block_slug
	 *
	 * @return Block
	 */
	private function block( $block_slug ) {
		if( ! array_key_exists( $block_slug, $this->blocks) ) {
			$this->blocks[ $block_slug ] = new Block( $block_slug );
		}

		return $this->blocks[ $block_slug ];
	}


	/**
	 * Returns OpeningBlockTag for block slug.
	 *
	 * @param $block_slug
	 *
	 * @return OpeningBlockTag
	 */
	public function opening_block_tag( $block_slug ) {
		return new OpeningBlockTag( $this->block( $block_slug ) );
	}


	/**
	 * Returns ClosingBlockTag for block slug.
	 *
	 * @param $block_slug
	 *
	 * @return ClosingBlockTag
	 */
	public function closing_block_tag( $block_slug ) {
		return new ClosingBlockTag( $this->block( $block_slug ) );
	}


	/**
	 * Returns start of view search position.
	 *
	 * @return StartOfViewEditor
	 */
	public function start_of_view_editor() {
		return new StartOfViewEditor();
	}
}
