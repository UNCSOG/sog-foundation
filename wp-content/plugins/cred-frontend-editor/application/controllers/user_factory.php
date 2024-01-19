<?php

/**
 * Factory for instantiating user objects for the purpose of unit testing.
 *
 * @since fixme add info
 */
class CRED_User_Factory {

	/**
	 * @param int|string|stdClass|WP_User $user
	 *
	 * @return WP_User
	 */
	public function get_user( $user ) {
		return new WP_User( $user );
	}

	/**
	 * @param $user_obj
	 * @param $what
	 *
	 * @return mixed
	 */
	public function get( $user_obj, $what ) {
		return $user_obj->get( $what );
	}

}