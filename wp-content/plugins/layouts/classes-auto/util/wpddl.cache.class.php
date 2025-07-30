<?php

class WPDDL_Cache {

	private $name;
	private $data = array();

	public function __construct( $name ) {
		$this->name = $name;
	}

	public function has( $key ) {
		return isset( $this->data[ $key ] );
	}

	public function set( $key, $value ) {
		$this->data[ $key ] = $value;
	}

	public function get( $key ) {
		if ( isset ( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		} else {
			return null;
		}
	}

	public function clear( $key ) {
		unset( $this->data[ $key ] );
	}
}