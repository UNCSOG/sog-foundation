<?php

namespace OTGS\Toolset\Access\Controllers;

/**
 * Main backend class
 * Class Backend
 *
 * @package OTGS\Toolset\Access\Controllers
 * @since 2.7
 */
class Backend {

	private static $instance;


	/**
	 * @return Backend
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function initialize() {
		self::get_instance();
	}


	/**
	 * Backend constructor.
	 */
	function __construct() {
		$backend_filters = \OTGS\Toolset\Access\Controllers\Filters\BackendFilters::get_instance();

		add_action( 'admin_enqueue_scripts', array( $backend_filters, 'toolset_access_select_group_metabox_files' ) );
		add_action( 'admin_head', array( $backend_filters, 'toolset_access_select_group_metabox' ) );

		add_filter( 'user_has_cap', array( $backend_filters, 'toolset_access_has_cap_filter' ), 0, 4 );

	}
}
