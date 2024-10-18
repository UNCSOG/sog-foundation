<?php

class WPDD_Listing_Store_Controller_Single extends WPDD_Listing_Store_Controller_Abstract implements WPDD_Listing_Store_Controller_Interface{
	protected $id = 2;
	protected $group_slug = 'single';
	protected $store = null;

	public function __construct( $store = null, $group_slug = null, $status = 'publish', $args = array() ) {
		parent::__construct( $store, $group_slug, $status, $args );
		$this->title = __('Layouts being used to display single posts or pages', 'ddl-layouts');
	}
}