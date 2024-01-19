<?php

/**
 * Abstraction Class that includes common functions used by post Fields and user UserFields
 *
 * @since 1.9.2
 */
abstract class CRED_Fields_Abstract_Model {

	/*
	 * Generic Custom Fields option const
	 * All the fields that are controlled by Toolset Forms are stored here
	 */
	const CUSTOM_FIELDS_OPTION = '__CRED_CUSTOM_FIELDS';

	protected $wpdb = null;

	function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * @param $post_type
	 * @param array $exclude_fields
	 * @param bool $show_private
	 * @param $paged
	 * @param int $perpage
	 * @param string $orderby
	 * @param string $order
	 *
	 * @return mixed
	 */
	abstract public function getPostTypeCustomFields( $post_type, $exclude_fields = array(), $show_private = true, $paged = 1, $perpage = 10, $orderby = 'meta_key', $order = 'asc' );

	/**
	 * @param null $post_type
	 * @param bool $force_all
	 *
	 * @return mixed
	 */
	abstract public function getCustomFields( $post_type = null, $force_all = false );

	/**
	 * @param $fields
	 */
	protected function save_custom_fields( $fields ) {
		update_option( self::CUSTOM_FIELDS_OPTION, $fields );
	}

}
