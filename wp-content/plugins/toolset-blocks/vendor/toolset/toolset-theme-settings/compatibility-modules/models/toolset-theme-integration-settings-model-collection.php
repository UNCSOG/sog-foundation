<?php

class Toolset_Theme_Integration_Settings_Model_Collection implements IteratorAggregate {
	protected $type;
	protected $label;
	protected $items;
	private $target = '';

	public function __construct( $type, $label ) {
		$this->type = $type;
		$this->label = $label;
	}

	public function getIterator() {
		return $this->items;
	}

	public function addItem( $obj, $key = null ) {

		if( $obj instanceof Toolset_Theme_Integration_Settings_Model_Interface === false ){
			throw new Exception( sprintf( '%s type is not supported by this Collection, only implementations of %s interface are allowed!', gettype( $obj ), 'Toolset_Theme_Integration_Settings_Model_Interface') );
		}

		if( $this->type !== $obj->get_type() ){
			throw new Exception( sprintf( '%s type is not supported by this Collection, object with type property equals to %s are allowed!', $obj->get_type(), $this->type) );
		}

		if ($key == null) {
			$this->items[] = apply_filters( 'toolset_theme_settings_integration_model_added_'. $obj->get_type() . '_' . $obj->get_name() , $obj, $this );
		}
		else {
			if (isset($this->items[$key])) {
				throw new Exception("Key $key already in use.");
			}
			else {
				$this->items[$key] = apply_filters( 'toolset_theme_settings_integration_model_added_'. $obj->get_type() . '_' . $obj->get_name() , $obj, $this );
			}
		}
	}

	public function deleteItem($key) {
		if (isset($this->items[$key])) {
			unset($this->items[$key]);
    }
		else {
			throw new Exception("Invalid key $key.");
		}
	}

	public function getItem($key) {
		if (isset($this->items[$key])) {
			return $this->items[$key];
		}
		else {
			throw new Exception("Invalid key $key.");
		}
	}

	public function length() {
		return count($this->items);
	}

	public function keyExists($key) {
		return isset($this->items[$key]);
	}

	public function where( $property, $value ){
		return array_values( array_filter( $this->items, array( new Toolset_Theme_Settings_Array_Utils( $property, $value ) , 'filter_array' ) ) );
	}

	public function get_models_by_target( $target ){
		$this->target = $target;
		return array_values( array_filter( $this->items, array( $this, 'filter_by_target' ) ) );
	}

	public function filter_by_target( $model ){
		return in_array( $this->target, $model->target );
	}

	public function get_type(){
		return $this->type;
	}

	public function get_label(){
		return $this->label;
	}
}