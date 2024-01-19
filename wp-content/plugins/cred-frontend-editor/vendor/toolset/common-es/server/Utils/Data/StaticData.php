<?php

namespace ToolsetCommonEs\Utils\Data;

class StaticData implements IData {
	/** @var array */
	private $data;

	public function __construct( $data ) {
		if( ! is_array( $data ) ) {
			throw new \InvalidArgumentException( '$data must be an array.' );
		}

		$this->data = $data;
	}


	public function find( $needle, $return_on_false = false ) {
		return $this->find_in( $needle, $this->data, $return_on_false );
	}

	public function find_in( $needle, $values, $return_on_false = false ) {
		if( ! is_array( $needle ) ) {
			$needle = [ $needle ];
		}

		if( empty( $needle ) ) {
			return $return_on_false;
		}

		$return = $values;

		foreach( $needle as $find ) {
			if( is_array( $return ) && array_key_exists( $find, $return ) ) {
				$return = $return[ $find ];
			} else {
				return $return_on_false;
			}
		}

		return $return;
	}
}
