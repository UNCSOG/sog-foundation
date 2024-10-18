<?php

class WPDD_Listing_Store_Controller_Archives extends WPDD_Listing_Store_Controller_Abstract implements WPDD_Listing_Store_Controller_Interface{
	protected $id = 4;
	protected $group_slug = 'archives';
	protected $store = null;

	public function __construct( $store = null, $group_slug = null, $status = 'publish', $args = array() ) {
		parent::__construct( $store, $group_slug, $status, $args );
		$this->title = __('Layouts being used to customize archives', 'ddl-layouts');
	}
}