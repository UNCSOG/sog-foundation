<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain;

/**
 * Class PostContent
 *
 * Value Object for post content. This wouldn't be needed if string type hinting would be available.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain
 *
 * @since TB 1.3
 */
class PostContent {
	/** @var string */
	private $content;

	/** @var IPostContentType */
	private $type;

	/** @var int|bool */
	private $position_start;

	/** @var int|bool */
	private $position_search;

	/** @var int|bool */
	private $position_loop;

	/** @var bool */
	private $has_closing_div;

	/** @var string */
	private $end_of_content = '';

	public function __construct( IPostContentType $type ) {
		$this->type = $type;
	}

	/**
	 * @param string $content
	 */
	public function set( $content ){
		if( ! is_string( $content ) ) {
			throw new \InvalidArgumentException( '$content must be a string.' );
		}

		// When content changes reset positions.
		$this->position_search = null;
		$this->position_loop = null;
		$this->position_start = null;
		$this->has_closing_div = null;
		$this->end_of_content = '';

		// Store new content.
		$this->content = $content;
	}

	/**
	 * @return string
	 */
	public function get() {
		return $this->content;
	}


	/**
	 * @return false|int
	 */
	public function position_start() {
		if( $this->position_start === null ) {
			$this->position_start = strpos( $this->get(), '<div class="' . $this->type->get_root_class() );
		}

		return $this->position_start;
	}


	/**
	 * @return false|int
	 */
	public function position_loop() {
		if( $this->position_loop === null ) {
			$this->position_loop = strpos( $this->get(), '[wpv-layout-meta-html]' );
		}

		return $this->position_loop;
	}


	/**
	 * @return false|int
	 */
	public function position_search() {
		if( $this->position_search === null ) {
			$this->position_search = strpos( $this->get(), '[wpv-filter-meta-html]' );
		}

		return $this->position_search;
	}


	/**
	 * @return bool
	 */
	public function ends_with_closing_div_tag() {
		if( $this->has_closing_div === null ) {
			$end_of_content = '</div>';
			$closing_div_length = strlen( $end_of_content );

			$this->has_closing_div = substr( $this->get(), - $closing_div_length ) === $end_of_content;

			if( ! $this->has_closing_div ) {
				// When there is no search added in the editor, Views applies the shortcode to the end.
				// No idea why.
				$end_of_content = '</div>[wpv-filter-meta-html]';
				$closing_div_length = strlen( $end_of_content );

				$this->has_closing_div =
					substr( $this->get(), - $closing_div_length ) === $end_of_content;
			}

			if( $this->has_closing_div ) {
				$this->end_of_content = $end_of_content;
			}
		}

		return $this->has_closing_div;
	}

	/**
	 * Apply translation between start of the view block and the search container.
	 *
	 * @param $translation
	 */
	public function apply_translation_between_start_and_search( $translation ) {
		if( empty( $translation ) ) {
			return;
		}

		$original_content = $this->get();

		// View start position.
		$view_opening_tag_start_position = $this->position_start();

		// Trim everything before view start and get end position of opening view div.
		$content_without_anything_before_view = substr( $original_content, $view_opening_tag_start_position );
		$view_opening_tag_end_position =
			strpos( $content_without_anything_before_view, '>' ) + 1 + $view_opening_tag_start_position ;

		// Search position.
		$search_position = $this->position_search();

		// Get pieces until view start and starting from search.
		$content_to_start = substr( $original_content, 0, $view_opening_tag_end_position );
		$content_from_search = substr( $original_content, $search_position );

		// Replace content with translation.
		$this->set( $content_to_start . $translation . $content_from_search );
	}

	/**
	 * Apply translation between the search container and the loop.
	 * Search must be first in this scenario.
	 *
	 * @param $translation
	 */
	public function apply_translation_between_search_and_loop( $translation ) {
		if( empty( $translation ) ) {
			return;
		}

		$original_content = $this->get();

		// Search start.
		$search_position_start = $this->position_search();

		// Trim everything before search start and get end position of search shortcode.
		$content_without_anything_before_view = substr( $original_content, $search_position_start );
		$search_position_end =
			strpos( $content_without_anything_before_view, ']' ) + 1 + $search_position_start ;

		// Loop position.
		$loop_start = $this->position_loop();

		// Get pieces until search and starting from loop.
		$content_to_search = substr( $original_content, 0, $search_position_end );
		$content_from_loop = substr( $original_content, $loop_start );

		// Replace content with translation.
		$this->set( $content_to_search . $translation . $content_from_loop );
	}

	/**
	 * Apply translation between the loop and the search container.
	 * Loop must be first in this scenario.
	 *
	 * @param $translation
	 */
	public function apply_translation_between_loop_and_search( $translation ) {
		if( empty( $translation ) ) {
			return;
		}

		$original_content = $this->get();

		// Loop start.
		$loop_position_start = $this->position_loop();

		// Trim everything before loop start and get end position of opening loop shortcode.
		$content_without_anything_before_loop = substr( $original_content, $loop_position_start );
		$loop_position_end =
			strpos( $content_without_anything_before_loop, ']' ) + 1 + $loop_position_start ;

		// Search position.
		$search_start = $this->position_search();

		// Get pieces until loop start and starting from search.
		$content_to_loop = substr( $original_content, 0, $loop_position_end );
		$content_from_search = substr( $original_content, $search_start );

		// Replace content with translation.
		$this->set( $content_to_loop . $translation . $content_from_search );
	}

	/**
	 * Apply translation between the start of the view block and the loop.
	 *
	 * @param $translation
	 */
	public function apply_translation_between_start_and_loop( $translation ) {
		if( empty( $translation ) ) {
			return;
		}

		$original_content = $this->get();

		// View start position.
		$view_opening_tag_start_position = $this->position_start();

		// Trim everything before view start and get end position of opening view div.
		$content_without_anything_before_view = substr( $original_content, $view_opening_tag_start_position );
		$view_opening_tag_end_position =
			strpos( $content_without_anything_before_view, '>' ) + 1 + $view_opening_tag_start_position ;

		// Loop position.
		$loop_position = $this->position_loop();

		// Get pieces until view start and starting from loop.
		$content_to_start = substr( $original_content, 0, $view_opening_tag_end_position );
		$content_from_search = substr( $original_content, $loop_position );

		// Replace content with translation.
		$this->set( $content_to_start . $translation . $content_from_search );
	}

	/**
	 * Apply translation between the search container and the end of the view block.
	 *
	 * @param $translation
	 */
	public function apply_translation_between_search_and_end( $translation ) {
		if( empty( $translation ) ) {
			return;
		}

		$original_content = $this->get();

		// Search start.
		$search_position_start = $this->position_search();

		// Trim everything before search start and get end position of search shortcode.
		$content_without_anything_before_view = substr( $original_content, $search_position_start );
		$search_position_end =
			strpos( $content_without_anything_before_view, ']' ) + 1 + $search_position_start ;

		// Get pieces until view start and starting from loop.
		$content_to_search = substr( $original_content, 0, $search_position_end );

		// Replace content with translation.
		$this->set( $content_to_search . $translation . $this->end_of_content() );
	}

	/**
	 * Apply translation between the loop and the end of the view block.
	 * Here is some oddness in the database storage. When there is only a loop,
	 * Views still apply [wpv-filter-meta-html] after the closing div. No clue why.
	 *
	 * @param $translation
	 */
	public function apply_translation_between_loop_and_end( $translation ) {
		if( empty( $translation ) ) {
			return;
		}

		$original_content = $this->get();

		// Loop start.
		$loop_position_start = $this->position_loop();

		// Trim everything before loop start and get end position of opening loop shortcode.
		$content_without_anything_before_loop = substr( $original_content, $loop_position_start );
		$loop_position_end =
			strpos( $content_without_anything_before_loop, ']' ) + 1 + $loop_position_start ;

		// Search position.
		$search_start = $this->position_search();

		// Get pieces until loop start and starting from search.
		$content_to_loop = substr( $original_content, 0, $loop_position_end );

		// Replace content with translation.
		$this->set( $content_to_loop . $translation . $this->end_of_content() );
	}


	/**
	 * This function is just needed because Views does apply the shortcode for search container,
	 * after the closing div. This happens when a view HAS NO SEARCH applied by the user.
	 *
	 * @return string
	 */
	private function end_of_content() {
		if( $this->ends_with_closing_div_tag() ) {
			return $this->end_of_content;
		}

		return '';
	}
}
