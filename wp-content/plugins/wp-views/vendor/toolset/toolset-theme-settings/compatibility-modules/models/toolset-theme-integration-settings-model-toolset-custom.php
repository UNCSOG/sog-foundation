<?php

class Toolset_Theme_Integration_Settings_Model_toolset_custom extends Toolset_Theme_Integration_Settings_Model_Abstract{
	public function update_current_value( $object_id = 0 ){

	}

	public function save_current_value( $object_id = 0 ){

	}

	protected function validate( $settings_object ) {
		if( !property_exists( $settings_object, 'type') || !in_array( $this->type, $settings_object->type ) ){
			return false;
		}
		return true;
	}
}