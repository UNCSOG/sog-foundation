<?php

class Toolset_Theme_Integration_Settings_Model_local extends Toolset_Theme_Integration_Settings_Model_Abstract{

	public function update_current_value( $object_id = null ){

	}

	/**
	 * @param null $object_id
	 *
	 * @return bool|int
	 */
	public function save_current_value( $object_id = null ){

		if( !$object_id ){
			return false;
		}

		$prev_value = $this->get_current_saved_value( $object_id );

		if( $this->get_current_value() === NULL ){
			return delete_post_meta( $object_id, $this->name,  $prev_value );
		} elseif(
			$prev_value === $this->get_current_value()
			|| $this->get_current_value() === self::TOOLSET_DEFAULT
		){
			return false;
		} else {
			return update_post_meta( $object_id, $this->name, $this->get_current_value(), $prev_value );
		}
	}

	/**
	 * @param $settings_object
	 *
	 * @return bool
	 */
	protected function validate( $settings_object ) {
		if( !property_exists( $settings_object, 'type') || !in_array( $this->type, $settings_object->type ) ){
			return false;
		}
		return true;
	}

	/**
	 * @param null $object_id
	 *
	 * @return mixed|null
	 */
	public function get_current_saved_value( $object_id = null ){
		if( !$object_id ) return null;

		return get_post_meta( $object_id, $this->name, true );
	}

}
