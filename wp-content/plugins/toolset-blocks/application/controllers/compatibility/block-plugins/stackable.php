<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\BlockPlugin;

use OTGS\Toolset\Views\Controller\Compatibility\Base;

/**
 * Handles the compatibility between Views and Stackable blocks.
 */
class StackableCompatibility extends Base {

	/**
	 * Initializes the Genesis blocks integration.
	 */
	public function initialize() {
		$this->init_hooks();
	}


	/**
	 * Initializes the hooks for the Stackable integration.
	 */
	private function init_hooks() {
		add_filter( 'get_post_metadata', array( $this, 'short_circuit_getting_optimized_css_meta' ), 10, 3 );
	}


	/**
	 * @param {string} $value
	 * @param {string} $object_id
	 * @param {string} $meta_key
	 *
	 * @return string
	 */
	public function short_circuit_getting_optimized_css_meta( $value, $object_id, $meta_key ) {
		if ( 'stackable_optimized_css' !== $meta_key ) {
			return $value;
		}

		return '';
	}
}
