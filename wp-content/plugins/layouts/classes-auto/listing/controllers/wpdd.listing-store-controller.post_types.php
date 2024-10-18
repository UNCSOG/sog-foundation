<?php

class WPDD_Listing_Store_Controller_Post_Types extends WPDD_Listing_Store_Controller_Abstract implements WPDD_Listing_Store_Controller_Interface{
	protected $id = 3;
	protected $group_slug = 'post_types';
	protected $store = null;

	public function __construct( $store = null, $group_slug = null, $status = 'publish', $args = array() ) {
		parent::__construct( $store, $group_slug, $status, $args );
		$this->title = __('Layouts being used as templates for post types', 'ddl-layouts');
	}
}