<?php

/**
 * Render the current View loop index.
 *
 * @since 2.7.3
 */
class WPV_Shortcode_Loop_Index extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-loop-index';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'accumulate' => 'true',
		'pad' => 'true',
		'offset' => '0',
	);

	/**
	 * @var string|null
	 */
	private $user_content;

	/**
	 * @var array
	 */
	private $user_atts;

	/**
	 * @var \OTGS\Toolset\Views\Controller\Frontend\LoopIndex;
	 */
	private $loop_index_controller;

	/**
	 * Shortcode constructor
	 */
	public function __construct( \OTGS\Toolset\Views\Controller\Frontend\LoopIndex $loop_index_controller ) {
		$this->loop_index_controller = $loop_index_controller;
	}

	/**
	 * Maybe modify an index count by the shortcode offset attribute.
	 *
	 * @param int $index
	 * @return int
	 */
	private function get_offset_index( $index ) {
		return $index + intval( toolset_getarr( $this->user_atts, 'offset' ) );
	}

	/**
	 * Get the current loop index when it should include accumulated counts by pagination.
	 *
	 * @param int $view_id
	 * @return int
	 */
	private function get_accumulated_value( $view_id ) {
		$partial_index = $this->loop_index_controller->get_loop_index_per_view( $view_id );
		$accumulated_index = $this->loop_index_controller->get_accumulated_index_per_view( $view_id, $this->user_atts );

		return $this->get_offset_index( $partial_index + $accumulated_index );
	}

	/**
	 * Get the current loop index when it does not need to include accumulated counts by pagination.
	 *
	 * @param int $view_id
	 * @return int
	 */
	private function get_simple_value( $view_id ) {
		$current_index = $this->loop_index_controller->get_loop_index_per_view( $view_id );

		return $this->get_offset_index( $current_index );
	}

	/**
	* Get the shortcode output value.
	*
	* @param array $atts
	* @param string $content
	* @return string
	* @since 2.7.3
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		$current_view = apply_filters( 'wpv_filter_wpv_get_current_view', 0 );

		if ( ! $current_view ) {
			return $this->get_offset_index( 0 );
		}

		if ( 'true' === toolset_getarr( $this->user_atts, 'accumulate' ) ) {
			return $this->get_accumulated_value( $current_view );
		}

		return $this->get_simple_value( $current_view );
	}

}
