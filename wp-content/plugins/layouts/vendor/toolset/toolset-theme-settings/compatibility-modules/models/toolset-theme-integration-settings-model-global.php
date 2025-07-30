<?php

class Toolset_Theme_Integration_Settings_Model_global extends Toolset_Theme_Integration_Settings_Model_Abstract{

	public $global_key = '';
	public $redux_global_key = '';

	/**
	 * Toolset_Theme_Integration_Settings_Model_global constructor.
	 *
	 * @param $type
	 */
	public function __construct( $type, $name ) {
		parent::__construct( $type, $name );
		//add_filter( 'toolset_theme_settings_integration_model_added_'. $this->type . '_' . $this->name, array( $this, 'added_callback' ), 10, 2 );
	}

	function added_callback( $model, $collection ){
		$this->update_current_value(  );
		return $model;
	}

	public function update_current_value( $object_id = 0 ){

		$options = $this->get_option();

		if( isset( $options[$this->name] ) ){
			$value = $options[$this->name];
		} else {
			$value = null;
		}

		$this->set_current_value( $value );
	}

	/**
	 * @param int $object_id
	 *
	 * @return bool
	 */
	public function save_current_value( $object_id = 0 ){
		return $this->update_options();
	}

	protected function validate( $settings_object ) {
		if( !property_exists( $settings_object, 'type') || !in_array( $this->type, $settings_object->type ) ){
			return false;
		}

		if( !property_exists( $settings_object, 'global_key') ){
			return false;
		}

		return true;
	}

	/**
	 * @return mixed|void
	 */
	protected function get_option(){
		return get_option( $this->global_key, null );
	}

	/**
	 * @return bool
	 */
	protected function update_options( ){

		$options = $this->get_option();
		$value = $this->get_current_value();

		if( !$value && ( !is_array( $options ) || !isset( $options[$this->name] ) ) ){
			return false;
		}

		if( is_array( $options ) ){
			$options[$this->name] = $value;
		} else {
			$options = array(
				$this->name => $value
			);
		}

		return update_option( $this->gobal_key, $options );
	}

	public function get_redux_global_key(){
		return $this->redux_global_key;
	}
}