<?php

namespace OTGS\Toolset\Views\Controller\Frontend;

/**
 * Fontend loop index manager.
 *
 * @since 2.7.3
 */
class LoopIndex {

	/**
	 * Store the current index for each rendered loop, using the View ID as key.
	 * This gets initialized whenever a loop starts, and cleared when it ends.
	 *
	 * @var array
	 */
	private $loop_index_per_view = array();

	/**
	 * Store the loop arguments for each rendered loop, using the View ID as key.
	 * Those arguments include information about the loop wrap and pad settings.
	 *
	 * @var array
	 */
	private $loop_args_per_view = array();

	/**
	 * Store the query type for each rendered loop, using the View ID as key.
	 * The View query type provides information to get the View query
	 * to calculate the amount of items on previous pages.
	 *
	 * @var array
	 */
	private $query_type_per_view = array();

	/**
	 * Initialize the controller.
	 *
	 * @since 2.7.3
	 */
	public function initialize() {
		$this->add_hooks();
	}

	/**
	 * Add the required hooks to control the frontend loop index.
	 *
	 * @since 2.7.3
	 */
	private function add_hooks() {
		// Re-initialize the index for a given View as it starts to loop over results
		add_action( 'wpv_action_wpv_loop_before', array( $this, 'start_loop_index_per_view' ), 10, 3 );
		// Record a loop item being rendered, whether it is an actual item or a ghost pad
		add_action( 'wpv_action_wpv_loop_before_display_item', array( $this, 'record_loop_item_per_view' ), 10, 3 );
		add_action( 'wpv_action_wpv_loop_before_display_pad_item', array( $this, 'record_pad_loop_item_per_view' ), 10, 2 );
		// Clean the index oce the View loop has finished
		add_action( 'wpv_action_wpv_loop_after', array( $this, 'clear_loop_index_per_view' ), 10 );
	}

	/**
	 * Clear the index for a View before its loop is rendered.
	 *
	 * @param int $view_id
	 * @param string $query_type The View query type.
	 * @param array $loop_args {
	 *     Loop arguments, if any, used when items are wrapped into rows.
	 *
	 *     @type int $wrap Optional. Number of items that each row sould include. If not set, all items will go into a single symbolic row.
	 *     @type bool $pad Optional. Whether the loop should include ghost items to complete a row, in case the items in a page do not cover the last row.
	 * }
	 */
	public function start_loop_index_per_view( $view_id, $query_type, $loop_args ) {
		$this->set_loop_index_per_view( $view_id, 0 );
		$this->record_query_type( $view_id, $query_type );
		$this->record_loop_args( $view_id, $loop_args );
	}

	/**
	 * Automatically update the View loop index right before rendering a View loop item.
	 *
	 * @param mixed $item The object about to be rendered, changes depending on the View type.
	 * @param string $query_type The View query type.
	 * @param int $view_id
	 */
	public function record_loop_item_per_view( $item, $query_type, $view_id ) {
		$current_count = $this->get_loop_index_per_view( $view_id );
		$this->set_loop_index_per_view( $view_id, $current_count + 1 );
	}

	/**
	 * Automatically update the View loop counter right before rendering a View loop pad item.
	 *
	 * @param string $query_type The View query type.
	 * @param int $view_id
	 */
	public function record_pad_loop_item_per_view( $query_type, $view_id ) {
		$current_count = $this->get_loop_index_per_view( $view_id );
		$this->set_loop_index_per_view( $view_id, $current_count + 1 );
	}

	/**
	 * Clear the index for a View after its loop is rendered.
	 *
	 * @param int $view_id
	 */
	public function clear_loop_index_per_view( $view_id ) {
		$this->set_loop_index_per_view( $view_id, 0 );
	}

	/**
	 * Get the loop index for a given View.
	 *
	 * @param int $view_id
	 * @return int The count of the View loop, starting at 1, or 0 if outside a View loop.
	 */
	public function get_loop_index_per_view( $view_id ) {
		if ( ! array_key_exists( $view_id, $this->loop_index_per_view ) ) {
			$this->loop_index_per_view[ $view_id ] = 0;
		}

		return $this->loop_index_per_view[ $view_id ];
	}

	/**
	 * Set the loop index for a given View.
	 *
	 * @param int $view_id
	 * @param int $index
	 */
	private function set_loop_index_per_view( $view_id, $index ) {
		$this->loop_index_per_view[ $view_id ] = $index;
	}

	/**
	 * Keep track of every rendered View query type.
	 *
	 * @param int $view_id
	 * @param string $query_type
	 */
	private function record_query_type( $view_id, $query_type ) {
		$this->query_type_per_view[ $view_id ] = $query_type;
	}

	/**
	 * Keep track of every rendered View loop arguments, if any.
	 *
	 * @param int $view_id
	 * @param array $loop_args {
	 *     Loop arguments, if any, used when items are wrapped into rows.
	 *
	 *     @type int $wrap Optional. Number of items that each row sould include. If not set, all items will go into a single symbolic row.
	 *     @type bool $pad Optional. Whether the loop should include ghost items to complete a row, in case the items in a page do not cover the last row.
	 * }
	 */
	private function record_loop_args( $view_id, $loop_args ) {
		$this->loop_args_per_view[ $view_id ] = $loop_args;
	}

	/**
	 * Get the loop indexes accumulated by ghost pad items, unless:
	 * - pad is not activated in this loop.
	 * - or there are no wrap columns set in this loop.
	 * - or the number of items per page fills all wrap columns including the last one.
	 *
	 * @param int $view_id
	 * @param int $previous_pages
	 * @param int $items_per_page
	 * @return int
	 */
	private function get_accumulated_pad_index_per_view( $view_id, $previous_pages, $items_per_page ) {
		$loop_args = toolset_getarr( $this->loop_args_per_view, $view_id, array() );
		$loop_pad = toolset_getarr( $loop_args, 'pad', false );
		$loop_wrap = intval( toolset_getarr( $loop_args, 'wrap', 0 ) );

		if (
			// No pad to add means no accumulated pad
			! $loop_pad
			// No number of columns means single symbolic column, so no accumulated pad
			|| 0 === $loop_wrap
			// No remainder items to pad means no accumulated pad
			|| 0 === ( $items_per_page % $loop_wrap )
		) {
			return 0;
		}

		$pad_count_per_page = $loop_wrap - ( $items_per_page % $loop_wrap );

		return $previous_pages * $pad_count_per_page;
	}

	/**
	 * Get the loop indexes accumulated count by all the previous pages of the current View.
	 * Note that this includes also pad items, if any, and if the right
	 * shortcode attribute demands it.
	 *
	 * @param int $view_id
	 * @param array $modifiers {
	 *     Counter modifiers, if any, to decide whether the accumulated index should include pads.
	 *
	 *     @type bool $pad Optional. Whether the loop should include ghost items to complete a row, in case the items in a page do not cover the last row.
	 * }
	 * @return int
	 */
	public function get_accumulated_index_per_view( $view_id, $modifiers ) {
		$accumulated_index = 0;

		$previous_pages = 0;
		$items_per_page = 0;

		$query_type = toolset_getarr( $this->query_type_per_view, $view_id, 'posts' );

		switch ( $query_type ) {
			case 'taxonomy':
				$taxonomy_query = apply_filters( 'wpv_filter_wpv_get_taxonomy_query', array() );
				$previous_pages = toolset_getarr( $taxonomy_query, 'page_number', 1 ) - 1;
				$items_per_page = toolset_getarr( $taxonomy_query, 'items_per_page', 0 );
				break;
			case 'users':
				$user_query = apply_filters( 'wpv_filter_wpv_get_user_query', array() );
				$previous_pages = toolset_getarr( $user_query, 'page_number', 1 ) - 1;
				$items_per_page = toolset_getarr( $user_query, 'items_per_page', 0 );
				break;
			case 'posts':
			default:
				$post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
				if ( null !== $post_query ) {
					$previous_pages = toolset_getarr( $post_query->query, 'paged', 1 ) - 1;
					$items_per_page = toolset_getarr( $post_query->query, 'posts_per_page', 0 );
				}
				break;
		}

		$accumulated_index = $previous_pages * $items_per_page;

		if ( 'true' !== toolset_getarr( $modifiers, 'pad' ) ) {
			return $accumulated_index;
		}

		$accumulated_index += $this->get_accumulated_pad_index_per_view( $view_id, $previous_pages, $items_per_page );

		return $accumulated_index;
	}

}
