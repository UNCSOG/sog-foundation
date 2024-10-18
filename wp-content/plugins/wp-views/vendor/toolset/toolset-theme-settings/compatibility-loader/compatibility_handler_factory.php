<?php

class Toolset_Compatibility_Handler_Factory {

	/**
	 * @param string $class_name
	 *
	 * @return Toolset_Compatibility_Handler_Interface
	 */
	public function create( $class_name ) {
		if( ! class_exists( $class_name ) ) {
			throw new InvalidArgumentException( 'Non-existent compatibility handler name.' );
		}

		if( ! is_subclass_of( $class_name, 'Toolset_Compatibility_Handler_Interface' ) ) {
			throw new InvalidArgumentException( 'The provided classname is not a compatibility handler.' );
		}

		return new $class_name;
	}

}