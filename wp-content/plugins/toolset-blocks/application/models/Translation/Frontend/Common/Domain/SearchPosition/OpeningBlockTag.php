<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\SearchPosition;


use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\ISearchPosition;

/**
 * Class StartOfBlock
 *
 * Start and end position of starting block.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\SearchPosition
 */
class OpeningBlockTag implements ISearchPosition {
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
	 * Return start or end position (depending on the point_to_start setting) of the opening block tag.
	 *
	 * @param string $content
	 *
	 * @return false|int
	 */
	public function position_in_content( $content ) {
		return $this->point_to_start ?
			$this->start_position_in_content( $content ) :
			$this->end_position_in_content( $content );
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
	 * Returns the start position of the opening block tag.
	 *
	 * With "Some content <!-- block --> some content." as content the return would be 12 (start position of <!--).
	 *
	 * @param string $content
	 *
	 * @return false|int
	 */
	private function start_position_in_content( $content ) {
		// New block format.
		$block_opening_tag_start_position = strpos( $content, '<!-- wp:' . $this->block->slug() );
		if( $block_opening_tag_start_position !== false ) {
			return $block_opening_tag_start_position;
		}

		// Older WP version did not applied wp: to blocks.
		$block_opening_tag_start_position = strpos( $content, '<!-- ' . $this->block->slug() );
		if( $block_opening_tag_start_position !== false ) {
			return $block_opening_tag_start_position;
		}

		// The block does not exist in the content.
		return false;
	}


	/**
	 * Returns the end position of the opening block tag.
	 *
	 * With "Some content <!-- block --> some content." as content the return would be 26 (position after -->).
	 *
	 * @param string $content
	 *
	 * @return false|int
	 */
	private function end_position_in_content( $content ) {
		if( ! $block_opening_start_position = $this->start_position_in_content( $content ) ) {
			// The block does not exist in the content.
			return false;
		}

		// Getting the end position of the starting tag of the block needs to consider possible attributes of the block.
		// Remove everything before the block start.
		$content_starting_with_block = substr( $content, $block_opening_start_position );

		// Find the closing block ' -->'.
		$closing_block_chars = ' -->';
		$block_opening_end_position = strpos( $content_starting_with_block, $closing_block_chars );

		if( $block_opening_end_position === false ) {
			// Invalid content.
			throw new \InvalidArgumentException(
				'Content has a broken block which start tag is not being closed.'
			);
		}

		// Return position + the start position + the length of the closing chars
		// to get the right position in the context of the full content.
		return $block_opening_end_position + $block_opening_start_position + strlen( $closing_block_chars );
	}
}
