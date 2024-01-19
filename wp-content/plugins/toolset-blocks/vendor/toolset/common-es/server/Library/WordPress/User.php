<?php

namespace ToolsetCommonEs\Library\WordPress;

class User {
	/**
	 * @param string $capability
	 *
	 * @return bool
	 */
	public function current_user_can( $capability ) {
		return current_user_can( $capability );
	}

	public function is_user_logged_in() {
		return is_user_logged_in();
	}
}


