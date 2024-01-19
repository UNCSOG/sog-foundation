<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain;

use OTGS\Toolset\Views\Models\Translation\Common;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\IBlockContent;
use \OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\BlockContent as CommonBlockContent;

/**
 * Class BlockContent
 *
 * Value object for block content. Providing some helper function to get certain parts of the content.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain
 *
 * @since TB 1.3
 */
class BlockContent implements IBlockContent {
	/** @var string */
	private $content;

	/** @var bool */
	private $has_search = false;

	/** @var CommonBlockContent  */
	private $common;

	/**
	 * BlockContent constructor.
	 *
	 * @param string $some_content
	 * @param CommonBlockContent $common
	 */
	public function __construct( $some_content, CommonBlockContent $common ) {
		// Manual type check.
		if( ! is_string( $some_content ) ) {
			throw new \InvalidArgumentException( '$some_content must be a string.' );
		}

		// Store dependencies.
		$this->common = $common;

		// Get content of the WPA block.
		if( ! $this->content = $common->get_block_of_content( $some_content, 'toolset-views/wpa-editor' ) ) {
			throw new \InvalidArgumentException( '$some_markup does not contain a wpa editor block.' );
		}

		$this->has_search = $common->has_search_container_in_content( $some_content );
	}

	public function get() {
		return $this->content;
	}

	public function has_search() {
		return $this->has_search;
	}

	public function get_content_search_container() {
		if( $content = $this->common->get_search_container_of_content( $this->content ) ) {
			return $content;
		}

		return '';
	}

	public function get_content_between_start_and_search() {
		// Start = End position of the view container opening div tag.
		$opening_div_container_of_view = $this->common->factory_search_position()
			->start_of_view_editor()
			->change_context_to_wpa()
			->point_to_end();

		// End = Start position of the view search block opening tag.
		$opening_search_block_tag = $this->common->factory_search_position()
			->opening_block_tag( 'toolset-views/custom-search-container' )
			->point_to_start();

		// Get the content between Start of View and Search.
		$wanted = $this->common->get_content_between_search_positions(
			$this->content,
			$opening_div_container_of_view,
			$opening_search_block_tag
		);

		// Return finding or empty string.
		return $wanted !== false ?
			$wanted :
			'';
	}

	public function get_content_between_start_and_output() {
		// Start = End position of the view container opening div tag.
		$opening_div_container_of_view = $this->common->factory_search_position()
			->start_of_view_editor()
			->change_context_to_wpa()
			->point_to_end();

		// End = Start position of view layout block tag.
		$opening_view_layout_block = $this->common->factory_search_position()
			->opening_block_tag( 'toolset-views/view-layout-block' )
			->point_to_start();

		// Get the content between Start of View and Output.
		$wanted = $this->common->get_content_between_search_positions(
			$this->content,
			$opening_div_container_of_view,
			$opening_view_layout_block
		);

		if( $wanted === false ) {
			/*
			This point should never be reached with the current state of Views .
			But it may change once we allow to have separated standalone blocks for search and output .
		 	*/
			throw new \RuntimeException( 'The content of the WPA is missing the start' .
				' of the WPA block or the Output block.' );
		}

		return $wanted;
	}

	/**
	 * @return string
	 */
	public function get_content_between_search_and_output() {
		return $this->common->get_content_between_search_and_output( $this->content );
	}

	/**
	 * @return string
	 */
	public function get_content_between_output_and_search() {
		return $this->common->get_content_between_output_and_search( $this->content );
	}

	/**
	 * @return string
	 */
	public function get_content_between_search_and_end() {
		// Start = End position of the closing search block tag.
		$closing_search_block_tag = $this->common->factory_search_position()
			->closing_block_tag( 'toolset-views/custom-search-container' )
			->point_to_end();

		// End = Start position of closing wpa editor block tag.
		$closing_view_editor_tag = $this->common->factory_search_position()
			->closing_block_tag( 'toolset-views/wpa-editor')
			->point_to_start();

		// Get the content between Search and WPA End.
		$wanted = $this->common->get_content_between_search_positions(
			$this->content,
			$closing_search_block_tag,
			$closing_view_editor_tag
		);

		// Return finding or empty string.
		return $wanted !== false ?
			$wanted :
			'';
	}

	/**
	 * @return string
	 */
	public function get_content_between_output_and_end() {
		// Start = End position of the closing view layout block tag.
		$closing_view_layout_tag = $this->common->factory_search_position()
			->closing_block_tag( 'toolset-views/view-layout-block' )
			->point_to_end();

		// End = Start position of closing WPA editor block tag.
		$closing_view_editor_tag = $this->common->factory_search_position()
			->closing_block_tag( 'toolset-views/wpa-editor')
			->point_to_start();

		// Get the content between Search and WPA End.
		$wanted = $this->common->get_content_between_search_positions(
			$this->content,
			$closing_view_layout_tag,
			$closing_view_editor_tag
		);

		if( $wanted === false ) {
			// Shouldn't happen but may change once we allow to have separated standalone blocks for search and output.
			throw new \RuntimeException(
				'The content of the view is missing the end of the View block or the output block.'
			);
		}

		// Remove closing html container tag.
		$length = strlen( $wanted );
		for( $i = 1; $i <= $length; $i++ ) {
			if( substr( $wanted, -($i),1 ) === '<' ) {
				$wanted = substr(
					$wanted,
					0,
					$length - $i
				);
				break;
			}
		}

		return $wanted;
	}
}
