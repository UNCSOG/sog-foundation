<?php

namespace OTGS\Toolset\Views\Model\Wordpress;

/**
 * Wrapper for WordPress wpdb class.
 *
 * @since 2.8.1
 */
class Wpdb {

	/**
	 * Get the global $wpdb instance.
	 *
	 * @return wpdb
	 * @since 2.8.1
	 */
	public function get_wpdb() {
		global $wpdb;
		return $wpdb;
	}

}
