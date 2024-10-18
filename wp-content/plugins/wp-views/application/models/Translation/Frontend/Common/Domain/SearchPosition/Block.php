<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\SearchPosition;


class Block {
	private $slug;

	/**
	 * StartOfBlock constructor.
	 *
	 * @param $block_slug
	 */
	public function __construct( $block_slug ) {
		if( ! preg_match( '/^[a-z-]+\/[a-z-]+$/', $block_slug ) ) {
			throw new \InvalidArgumentException( 'Invalid slug.' );
		}

		$this->slug = $block_slug;
	}

	public function slug() {
		return $this->slug;
	}
}
