<?php

class Toolset_Theme_Integration_Settings_Model_customizer extends Toolset_Theme_Integration_Settings_Model_global {

	/**
	 * Extra theme mod handles that should get the same value than the main one.
	 *
	 * @var string[]
	 */
	public $aliases = [];


	public function update_current_value( $object_id = 0 ) { }


	public function save_current_value( $object_id = 0 ) { }


	protected function validate( $settings_object ) {
		return property_exists( $settings_object, 'type' ) && in_array( $this->type, $settings_object->type, true );
	}
}
