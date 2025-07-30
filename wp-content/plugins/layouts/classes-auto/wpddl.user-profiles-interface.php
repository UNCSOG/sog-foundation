<?php

interface WPDD_Layouts_Users_Profiles_Interface{
	public function wpddl_layouts_capabilities( $data );
	public function add_caps();
	public function disable_all_caps();
	public function get_wp_relative_cap();
	public function get_label();
	public static function ddl_get_capabilities();
	public function get_cap_for_page( $slug );
}