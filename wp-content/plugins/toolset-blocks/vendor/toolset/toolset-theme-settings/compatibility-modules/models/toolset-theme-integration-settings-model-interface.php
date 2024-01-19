<?php

interface Toolset_Theme_Integration_Settings_Model_Interface extends IteratorAggregate{
	public function update_current_value( $object_id = null );
	public function populate( $settings_object );
	public function save_current_value( $object_id = null );
	public function get_current_value();
	public function set_current_value( $value );
	public function get_current_switch_value();
	public function set_current_switch_value( $value );
}