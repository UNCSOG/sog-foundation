<?php

abstract class Toolset_Theme_Integration_Settings_Model_Abstract extends stdClass implements Toolset_Theme_Integration_Settings_Model_Interface{

	const TOOLSET_DEFAULT = 'toolset_use_theme_setting';
	
	/**
	 * @var null
	 * The Model type being global, local, customizer, toolset-custom or toolset-filter
	 */
	public $type = null;

	/**
	 * @var string
	 * prevents php errors, field is mandatory in JSON file
	 */
	public $name = '';

	/**
	 * @var null/array
	 * It stores for what kind of WP resource the settings is valid [archive, single]
	 */
	public $target = null;

	/**
	 * @var null/object/array
	 * Stores form fields for this settings object
	 */
	public $gui = null;

	/**
	 * @var string
	 * Stores the group name as a label ("General Settings", "Disable Elements", etc.)
	 */
	public $group = '';
	
	/**
	 * @var string
	 * Stores the type of data that this setting shoudl hold
	 */
	public $expected_type = 'keep';

	/**
	 * @var string
	 * Stores the default value, defaults to toolset_use_theme_setting
	 */
	public $default_value = 'toolset_use_theme_setting';

	/**
	 * @var null
	 * The value saved by the user in database if any, NULL defaults to user preferencies whose precedence is established by the theme
	 */
	protected $current_value = null;
	
	/**
	 * @var string
	 * Stores the default switch value, defaults to toolset_use_theme_setting
	 */
	public $default_switch_value = 'toolset_use_theme_setting';
	
	/**
	 * @var null
	 * The switch value for text settings
	 */
	protected $current_switch_value = null;

	public function __construct( $type, $name ) {
		$this->type = $type;
		$this->name = $name;
	}

	/**
	 * @param int $object_id
	 *
	 * @return mixed
	 * Updates $current_value with value from DB if any.
	 */
	public function update_current_value( $object_id = 0 ){
		// this method is meant to be overriden
	}

	/**
	 * @param int $object_id
	 *
	 * @return mixed
	 * Updates $current_value in database
	 */
	public function save_current_value( $object_id = 0 ){
		// this method is meant to be overriden
	}

	/**
	 * @return object
	 */
	public function getIterator()
	{
		return  (object) iterator_to_array( new RecursiveArrayIterator( $this ) );
	}

	/**
	 * @return null
	 */
	public function get_current_value(){
		return $this->current_value;
	}

	public function set_current_value( $value ){
		$this->current_value = $value;
	}

	public function get_default_value(){
		return $this->default_value;
	}
	
	public function get_current_switch_value(){
		return $this->current_switch_value;
	}
	
	public function set_current_switch_value( $value ){
		$this->current_switch_value = $value;
	}
	
	public function get_default_switch_value(){
		return $this->default_switch_value;
	}
	
	public function get_expected_value_type(){
		return $this->expected_type;
	}
	
	/**
	 * @return null
	 */
	public function get_type(){
		return $this->type;
	}

	public function get_name(){
		return $this->name;
	}
	
	public function get_gui_type() {
		return $this->gui->type;
	}
	
	public function get_referenced_label() {
		return empty( $this->gui->display_name ) ? $this->group : $this->gui->display_name;
	}

	/**
	 * @param $name
	 *
	 * @return null
	 */
	public function __get( $name ) {

		if( $this->__isset( $name ) ){
			return $this->{$name};
		}

		// phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
		$trace = debug_backtrace();
		trigger_error(
			'Undefined property via __get(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE);
		return null;
	}

	/**
	 * @param $property
	 *
	 * @return bool
	 */
	public function __isset( $property ){
		return property_exists( $this, $property );
	}

	/**
	 * @param $method
	 * @param $arguments
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($method, $arguments) {
		if ( method_exists( $this, $method ) ) {
			return call_user_func( array($this, $method), $arguments);
		} else {
			throw new Exception( spintf( "Fatal error: Call to undefined method %s::%s()", get_class( $this ), $method ) );
		}
	}

	/**
	 * @param $settings_object
	 *
	 * @return null|object
	 * Populates the Model Object with its properties after validating them, if settings object is not valid fails and returns NULL.
	 */
	public function populate( $settings_object ){

		if ( empty( $settings_object ) ) return null;

		if( !$this->validate( $settings_object ) ) return null;

		if ( !empty( $settings_object ) ) {
			foreach ( $settings_object as $property => $value ) {
				if( $property !== 'type' ){
					// you can override the value for a given property using this filter
					$this->{$property} = apply_filters( 'toolset_theme_integration_settings_model_set_' . $this->type . '_' . $this->name . '_' . $property, $value, $property, $this );
				}
			}
		}

		// you can hook into this filter to push properties into the model when it is just created/populated
		return apply_filters( 'toolset_theme_integration_settings_model_populated_' . $this->type . '_' . $this->name , $this->getIterator(), $this );
	}

	/**
	 * @param $settings_object
	 *
	 * @return mixed
	 * validates $settings_object against rules that varies per type
	 */
	abstract protected function validate( $settings_object );

}
