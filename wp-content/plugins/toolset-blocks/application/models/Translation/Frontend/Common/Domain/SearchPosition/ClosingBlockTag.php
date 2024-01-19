<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\SearchPosition;


use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\ISearchPosition;

/**
 * Class ClosingBlockTag
 *
 * Start and end position of opening block tag.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\SearchPosition
 */
class ClosingBlockTag implements ISearchPosition {
	/** @var Block  */
	private $block;

	/** @var bool */
	private $point_to_start = true;

	/**
	 * StartOfBlock constructor.
	 *
	 * @param Block $block
	 */
	public function __construct( Block $block ) {
		$this->block = $block;
	}

	/**
	 * Return start or end position (depending on the point_to_start setting) of the closing block tag.
	 *
	 * @param string $content
	 *
	 * @return false|int
	 */
	public function position_in_content( $content ) {
		return $this->point_to_start ?
			$this->position( $content ) :
			$this->position( $content, $return_end_position = true );
	}

	/**
	 * Point to the start position of the opening block tag.
	 * This is the default behaviour.
	 *
	 * @return $this
	 */
	public function point_to_start() {
		$this->point_to_start = true;

		return $this;
	}


	/**
	 * Point to the end position of the closing block tag.
	 *
	 * @return $this
	 */
	public function point_to_end() {
		$this->point_to_start = false;

		return $this;
	}

	/**
	 * The closing tag does not change with any block attributes as the opening tag does. So the logic is the same
	 * and just the pointer switches depending if the start or the end position is wanted.
	 *
	 * @param $content
	 * @param false $return_end_position
	 *
	 * @return false|int
	 */
	private function position( $content, $return_end_position = false ) {
		// New block format.
		$block_closing_tag = '<!-- /wp:' . $this->block->slug() . ' -->';
		$position = strpos( $content, $block_closing_tag );
		if( $position !== false ) {
			// Return start or end position of block closing block.
			return ! $return_end_position ? $position : $position + strlen( $block_closing_tag );
		}

		// Older WP version did not applied wp: to blocks.
		$block_closing_tag = '<!-- /' . $this->block->slug() . ' -->';
		$position = strpos( $content, $block_closing_tag );
		if( $position !== false ) {
			// Return start or end position
			return ! $return_end_position ? $position : $position + strlen( $block_closing_tag );
		}

		// The block does not exist in the content.
		return false;
	}
}
