<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain;

// Common Dependencies
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\IBlockContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\BlockContent as CommonBlockContent;

/**
 * Class BlockContent
 *
 * Value object. Just verifies that the given content is content of a View block and holds/serves it.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain
 *
 * @since TB 1.3
 */
class BlockContent implements IBlockContent {
	/** @var string Content of the Block. */
	private $content;

	/** @var bool */
	private $has_search = false;

	/** @var CommonBlockContent */
	private $common;

	/**
	 * BlockContent constructor.
	 *
	 * @param string $some_content
	 * @param ViewId $view_id
	 * @param CommonBlockContent $common
	 */
	public function __construct( $some_content, ViewId $view_id, CommonBlockContent $common	) {
		// Manual type check.
		if( ! is_string( $some_content ) ) {
			throw new \InvalidArgumentException( '$some_content must be a string.' );
		}

		// Store dependencies.
		$this->common = $common;

		// Get all views blocks of $some_content.
		$view_blocks = $common->get_blocks_by_slug_of_content( $some_content, 'toolset-views/view-editor' );

		if( empty( $view_blocks ) ) {
			// View does not exist.
			throw new \InvalidArgumentException(
				'$some_markup does not contain a view block with id ' . $view_id->get() . '.'
			);
		}

		// Find the view with the wanted $view_id (the content can container several Views).
		foreach( $view_blocks as $view_block ) {
			foreach( [ '"viewId":'.$view_id->get(), "'viewId':" . $view_id->get() ] as $view_id_prop ) {
				if( strpos( $view_block, $view_id_prop ) ) {
					// View exists.
					$this->content = $view_block;
					$this->has_search = $common->has_search_container_in_content( $view_block );
					return;
				}
			}
		}

		// View does not exist.
		throw new \InvalidArgumentException(
			'$some_markup does not contain a view block with id ' . $view_id->get() . '.'
		);
	}

	/**
	 * @return string
	 */
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
			This point should never be reached with the current state of Views.
			But it may change once we allow to have separated standalone blocks for search and output.
			*/
			throw new \RuntimeException( 'The content of the view is missing the start' .
				' of the View block or the Output block.' );
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
	 * Returns the content between search and end.
	 *
	 * @return string
	 */
	public function get_content_between_search_and_end() {
		// Start = End position of the closing search block tag.
		$closing_search_block_tag = $this->common->factory_search_position()
			->closing_block_tag( 'toolset-views/custom-search-container' )
			->point_to_end();

		// End = Start position of closing view editor block tag.
		$closing_view_editor_tag = $this->common->factory_search_position()
			->closing_block_tag( 'toolset-views/view-editor')
			->point_to_start();

		// Get the content between Search and View End.
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
	 * Returns the content between the output and the end.
	 *
	 * In the past a single preg_match() call was used: preg_match(
	 *			'#\/(?:wp:)?toolset-views\/view-layout-block -->(.*?)' .
	 *			'</[a-z]*>\s?<!-- \/(?:wp:)?toolset-views\/view-editor -->#ism'
	 * );
	 *
	 * That perform a bit better than the following solution, but the problem is that on very long post content the
	 * preg_match can ran into the backtrack limit and when that happens the returned string will be empty.
	 *
	 * The slowest variant would be using a parse_block on the content. The parser alone took over 1500% longer as the
	 * following and than it's still required to get the content BETWEEN to blocks.
	 *
	 * @return string
	 */
	public function get_content_between_output_and_end() {
		// Start = End position of the closing view layout block tag.
		$closing_view_layout_tag = $this->common->factory_search_position()
			->closing_block_tag( 'toolset-views/view-layout-block' )
			->point_to_end();

		// End = Start position of closing view editor block tag.
		$closing_view_editor_tag = $this->common->factory_search_position()
			->closing_block_tag( 'toolset-views/view-editor')
			->point_to_start();

		// Get the content between Search and View End.
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
