<?php

/**
 * Class WPDD_Listing_Store_Controller_Free
 */
class WPDD_Listing_Store_Controller_Free extends WPDD_Listing_Store_Controller_Abstract implements WPDD_Listing_Store_Controller_Interface{
	protected $id = 1;
	protected $group_slug = 'free';
	protected $store = null;

	/**
	 * WPDD_Listing_Store_Controller_Free constructor.
	 *
	 * @param null $store
	 */
	public function __construct( $store = null, $group_slug = null, $status = 'publish', $args = array() ) {
		parent::__construct( $store, $group_slug, $status, $args );
		$this->title = __("Unassigned Layouts", 'ddl-layouts');
	}
}