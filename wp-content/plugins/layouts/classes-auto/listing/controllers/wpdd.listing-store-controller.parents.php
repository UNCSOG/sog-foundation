<?php

class WPDD_Listing_Store_Controller_Parents extends WPDD_Listing_Store_Controller_Abstract implements WPDD_Listing_Store_Controller_Interface{
    protected $id = 5;
	protected $group_slug = 'parents';
	protected $store = null;

	public function __construct( $store = null, $group_slug = null, $status = 'publish', $args = array() ) {
		parent::__construct( $store, $group_slug, $status, $args );
		$this->title = __("Parents with all children assigned", 'ddl-layouts');
	}

	public function maybe_get_current_layouts_parents_list(){
		return array();
	}
}