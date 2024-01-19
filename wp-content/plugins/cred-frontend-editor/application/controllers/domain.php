<?php

/**
 * Class that holds the names of form domains.
 *
 * @since 2.5.4
 */
abstract class CRED_Form_Domain {

	const POSTS = 'posts';
	const USERS = 'users';
	const ASSOCIATIONS = 'relationships';


	public static function all() {
		return array( self::POSTS, self::USERS, self::ASSOCIATIONS );
	}

}