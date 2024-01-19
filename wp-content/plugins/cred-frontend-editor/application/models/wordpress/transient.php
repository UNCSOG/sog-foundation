<?php

namespace OTGS\Toolset\CRED\Model\Wordpress;


/**
 * Class Transient
 *
 * Wrapper for WordPress transient functions
 *
 * @package OTGS\Toolset\CRED\Model\Wordpress
 * 
 * @since 2.1.2
 */
class Transient {

	/**
	 * returns set_transient( $key, $value, $time_in_seconds )
	 * 
	 * @param $key
	 * @param $value
	 * @param $time_in_seconds
	 *
	 * @return bool
	 */
	public function set_transient( $key, $value, $time_in_seconds ) {
		return set_transient( $key, $value, $time_in_seconds );
	}
	/**
	 * returns get_transient( $key )
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function get_transient( $key ) {
		return get_transient( $key );
	}

	/**
	 * returns deletes_transient( $key );
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function delete_transient( $key ) {
		return delete_transient( $key );
	}
}