<?php

class CRED_Association_Form_Collection implements IteratorAggregate, CRED_Association_Form_Model_Interface {

	protected $items = array();

	public function __construct( $args = null ) {

	}

	public function getIterator() {
		return $this->items;
	}

	public function addItem( $obj, $key = null ) {

		if( $obj instanceof CRED_Association_Form_Model_Interface === false ){
			throw new Exception( sprintf( '%s type is not supported by this Collection, only implementations of %s interface are allowed!', gettype( $obj ), 'CRED_Association_Form_Model_Interface') );
		}

		if ($key == null) {
			$this->items[] = apply_filters( 'cred_association_form_model_added_' . $obj->get_slug() , $obj, $this );
		}
		else {
			if (isset($this->items[$key])) {
				throw new Exception("Key $key already in use.");
			}
			else {
				$this->items[$key] = apply_filters( 'cred_association_form_model_added_' . $obj->get_slug() , $obj, $this );
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

	public function populate( array $data ) {
		// TODO: Implement populate() method.
	}

	public function process_data() {
		// TODO: Implement process_data() method.
	}

	public function to_array(){
		return $this->getIterator();
	}
}