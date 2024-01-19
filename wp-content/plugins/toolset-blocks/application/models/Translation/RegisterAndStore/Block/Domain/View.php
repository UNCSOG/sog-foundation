<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain;

/**
 * Class View
 *
 * Value object for view related settings.
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain
 *
 * @since TB 1.3
 */
class View {
	/**
	 * The data stored in the _wpv_view_data post meta.
	 * @var array $view_data
	 */
	private $view_data = [];

	public function __construct( $view_data ) {
		if( ! is_array( $view_data ) || empty( $view_data ) ) {
			// Probably not an ID of a view.
			throw new \InvalidArgumentException( '$view_data must be a non-empty array.' );
		}

		$this->view_data = $view_data;
	}

	/**
	 * Get the "No items found" user text.
	 * This is also stored on the _wpv_view_data.
	 *
	 * @return string
	 */
	public function get_no_items_found_text() {
		if(
			array_key_exists( 'loop', $this->view_data ) &&
			array_key_exists( 'no_items_text', $this->view_data['loop'] )
		) {
			return $this->view_data['loop']['no_items_text'];
		}

		return '';
	}
}
