<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain;

use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\SearchPosition\Factory as FactorySearchPosition;

/**
 * Class BlockContent
 *
 * There are server similar parts in a View / WPA, which are hold by this.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain
 *
 * @since TB 1.3
 */
class BlockContent {
	/** @var FactorySearchPosition */
	private $factory_search_position;


	/**
	 * BlockContent constructor.
	 *
	 * @param FactorySearchPosition $factory_search_position
	 */
	public function __construct( FactorySearchPosition $factory_search_position ) {
		$this->factory_search_position = $factory_search_position;
	}


	/**
	 * @return FactorySearchPosition
	 */
	public function factory_search_position() {
		return $this->factory_search_position;
	}

	/**
	 * Returns the block of the given content.
	 * Beware: this does not handle multiple blocks of the same type and is just a helper for the Translation
	 * implementation to avoid some preg_match calls.
	 *
	 * @param string $content The content which is used to search for the block.
	 * @param string $block_slug This should be the full slug of the block, for example.
	 *
	 * @return false|string
	 */
	public function get_block_of_content( $content, $block_slug ) {
		$start_of_opening_block_tag = $this->factory_search_position()
			->opening_block_tag( $block_slug )
			->point_to_start();

		$end_of_closing_block_tag = $this->factory_search_position()
			->closing_block_tag( $block_slug )
			->point_to_end();

		return $this->get_content_between_search_positions(
			$content,
			$start_of_opening_block_tag,
			$end_of_closing_block_tag
		);
	}


	/**
	 * Get blocks of defined slug from content.
	 *
	 * @param $content
	 * @param $block_slug
	 *
	 * @return string[]
	 */
	public function get_blocks_by_slug_of_content( $content, $block_slug ){
		$blocks = [];

		while( $block = $this->get_block_of_content( $content, $block_slug ) ) {
			$blocks[] = $block;

			// Remove the current fetched block from content.
			$is_content_modified = false;
			foreach( [ "<!-- /wp:$block_slug -->", "<!-- /$block_slug -->" ] as $closing_block ) {
				if( $end_position = strpos( $content, $closing_block ) ) {
					$content = substr( $content, $end_position + strlen( $closing_block ) );
					$is_content_modified = true;
					break;
				}
			}

			if( ! $is_content_modified ) {
				// Shouldn't happen, but prevents for an endless loop if something is not working
				// with the removal of the already fetched block.
				break;
			}
		}

		return $blocks;
	}

	/**
	 * Returns the search container of $content.
	 *
	 * @param $content
	 *
	 * @return false|string
	 */
	public function get_search_container_of_content( $content ) {
		$start_search = $this->factory_search_position()
			->opening_block_tag( 'toolset-views/custom-search-container' )
			->point_to_start();

		$end_search = $this->factory_search_position()
			->closing_block_tag( 'toolset-views/custom-search-container' )
			->point_to_end();

		return $this->get_content_between_search_positions(
			$content,
			$start_search,
			$end_search
		);
	}


	/**
	 * Simple check for a search container in the given $content.
	 *
	 * @param $content
	 *
	 * @return bool
	 */
	public function has_search_container_in_content( $content ) {
		return strpos( $content, 'toolset-views/custom-search-container -->' ) !== false;
	}

	/**
	 * Returns the content between two positions in the content.
	 *
	 * @param $content
	 * @param ISearchPosition $start
	 * @param ISearchPosition $end
	 *
	 * @return false|string
	 */
	public function get_content_between_search_positions( $content, ISearchPosition $start, ISearchPosition $end ) {
		$start_position = $start->position_in_content( $content );
		$end_position = $end->position_in_content( $content );

		if( $start_position !== false && $end_position !== false ) {
			return substr( $content, $start_position, $end_position - $start_position );
		}

		return false;
	}


	/**
	 * Returns the content between search block and layout block.
	 *
	 * This is similar to View / WPA.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function get_content_between_search_and_output( $content ) {
		// Start = End position of the closing search block tag.
		$closing_search_block_tag = $this->factory_search_position()
			->closing_block_tag( 'toolset-views/custom-search-container' )
			->point_to_end();

		// End = Start position of view layout block tag.
		$opening_view_layout_block = $this->factory_search_position()
			->opening_block_tag( 'toolset-views/view-layout-block' )
			->point_to_start();

		// Get the content between Search and Output.
		$wanted = $this->get_content_between_search_positions(
			$content,
			$closing_search_block_tag,
			$opening_view_layout_block
		);

		// Return finding or empty string.
		return $wanted !== false ?
			$wanted :
			'';
	}


	/**
	 * Returns the content between the loop output and the search block.
	 * A relative rare case having the search at the bottom but it's possible.
	 *
	 * This is similar to View / WPA.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function get_content_between_output_and_search( $content ) {
		// Start = End position of the closing view layout block tag.
		$closing_layout_block_tag = $this->factory_search_position()
			->closing_block_tag( 'toolset-views/view-layout-block' )
			->point_to_end();

		// End = Start position of the opening search block tag.
		$opening_search_block_tag = $this->factory_search_position()
			->opening_block_tag( 'toolset-views/custom-search-container' )
			->point_to_start();

		// Get the content between Output and Search.
		$wanted = $this->get_content_between_search_positions(
			$content,
			$closing_layout_block_tag,
			$opening_search_block_tag
		);

		// Return finding or empty string.
		return $wanted !== false ?
			$wanted :
			'';
	}
}
