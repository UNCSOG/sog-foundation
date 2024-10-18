<?php

/**
 * Interface WPDD_Listing_Store_Controller_Interface
 */
interface WPDD_Listing_Store_Controller_Interface{
	public function get_layouts_list();
	public function build_store_and_return_it();
	public function maybe_get_current_layouts_parents_list();
	public function get_layouts_list_items();
}