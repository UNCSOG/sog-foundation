<?php

namespace OTGS\Toolset\Views;

/**
 * Domain class to unify query types for Views.
 *
 * @since 3.1
 */
class Domain {

	const POSTS = 'posts';

	const TERMS = 'taxonomy';

	const USERS = 'users';

	public static function all() {
		return array( self::POSTS, self::USERS, self::TERMS );
	}

}
