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
class StartOfViewEditor implements ISearchPosition {
	/** @var bool */
	private $point_to_start = true;

	private $css_class = 'wp-block-toolset-views-view-editor';


	/**
	 * Change the context to WPA instead of a page View.
	 * This will change the css class, which Views applies on the frontend.
	 *
	 * @return $this
	 */
	public function change_context_to_wpa() {
		$this->css_class = 'wp-block-toolset-views-wpa-editor';

		return $this;
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
	 * Returns the start position of the container div of a view editor.
	 *
	 * @param string $content
	 *
	 * @return false|int
	 */
	private function start_position_in_content( $content ) {
		// Find
		$start_position = strpos( $content, '<div class="' . $this->css_class );
		if( $start_position !== false ) {
			return $start_position;
		}

		// There is no View in the $content.
		return false;
	}


	/**
	 * Returns the end position of the container div of a view editor.
	 *
	 * @param string $content
	 *
	 * @return false|int
	 */
	private function end_position_in_content( $content ) {
		if( ! $start_position = $this->start_position_in_content( $content ) ) {
			// The block does not exist in the content.
			return false;
		}

		// Getting the end position of the container of the view needs to consider extra classes.
		// Remove everything before the container start.
		$content_starting_with_div_container = substr( $content, $start_position );

		// Find the closing block '>'.
		$closing_div_char = '>';
		$end_position = strpos( $content_starting_with_div_container, $closing_div_char );

		if( $end_position === false ) {
			// Invalid content.
			throw new \InvalidArgumentException(
				'Content has a broken view.'
			);
		}

		// Return position + the start position + the char of the closing div
		// to get the right position in the context of the full content.
		return $end_position + $start_position + strlen( $closing_div_char );
	}
}
