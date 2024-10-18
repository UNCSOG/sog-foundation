<?php

namespace OTGS\Toolset\Views\Controller\Cache\Meta;

use OTGS\Toolset\Views\Model\Wordpress\Transient;
use OTGS\Toolset\Views\Model\Wordpress\Wpdb;
use OTGS\Toolset\Views\Controller\Cache\Meta\Base;

/**
 * Meta cache controller.
 *
 * @since 2.8.1
 */
abstract class ManagerBase {

	const LIMIT = 512;

	const VISIBLE_KEY = '';
	const HIDDEN_KEY = '';

	/**
	 * @var Transient
	 */
	protected $transient_manager = null;

	/**
	 * @var Wpdb
	 */
	protected $wpdb = null;

	/**
	 * @var Base
	 */
	protected $meta_cache = null;

	/**
	 * Constructor
	 *
	 * @param Transient $transient_manager
	 * @param Wpdb $wpdb
	 * @since 2.8.1
	 * @since 2.8.2 Add \Wpdb as a dependency.
	 */
	public function __construct(
		Transient $transient_manager,
		Wpdb $wpdb
	) {
		$this->transient_manager = $transient_manager;
		$this->wpdb = $wpdb->get_wpdb();
	}

	/**
	 * Initialize this controller.
	 *
	 * @param Base $meta_cache
	 * @since 2.8.1
	 */
	public function initialize( Base $meta_cache ) {
		$this->meta_cache = $meta_cache;

		$this->add_hooks();
	}

	/**
	 * Register API hooks to get, generate or delete the cache.
	 *
	 * @since 2.8.1
	 */
	abstract protected function add_hooks();

	/**
	 * Get the cache for visible fields.
	 *
	 * @return array
	 * @since 2.8.1
	 */
	public function get_visible_cache() {
		$meta_cache = $this->meta_cache;
		return $this->transient_manager->get_transient( $meta_cache::VISIBLE_KEY );
	}

	/**
	 * Get existing or generate anew cache for visible meta fields.
	 *
	 * @param array $dummy
	 * @param int $limit
	 * @return array
	 * @since 2.8.1
	 */
	public function get_or_generate_visible_cache( $dummy = array(), $limit = self::LIMIT ) {
		if ( self::LIMIT !== $limit ) {
			return $this->generate_visible_query( $limit );
		}

		$cache = $this->get_visible_cache();

		if ( false !== $cache ) {
			return $cache;
		}

		return $this->generate_visible_cache();
	}

	/**
	 * Generate a new cache for visible meta fields,
	 * or generate a just-in-time query for non standards query limits.
	 *
	 * @return array
	 * @since 2.8.1
	 * @since 2.8.2 Turn method non abstract and move the abstract part to generate_visible_query.
	 */
	public function generate_visible_cache() {
		$meta_keys = $this->generate_visible_query( self::LIMIT );
		$this->set_visible_cache( $meta_keys );
		return $meta_keys;
	}

	/**
	 * Generate a new query for visible meta fields,
	 * each subclass must provide this method.
	 *
	 * @param int $limit
	 * @return array
	 * @since 2.8.2
	 */
	abstract public function generate_visible_query( $limit = self::LIMIT );

	/**
	 * Set the cache for visible fields.
	 *
	 * @param mixed $cache
	 * @return bool
	 * @since 2.8.1
	 */
	public function set_visible_cache( $cache ) {
		$meta_cache = $this->meta_cache;
		return $this->transient_manager->set_transient( $meta_cache::VISIBLE_KEY, $cache );
	}

	/**
	 * Delete the cache for visible fields.
	 *
	 * @return bool
	 * @since 2.8.1
	 */
	public function delete_visible_cache() {
		$meta_cache = $this->meta_cache;
		return $this->transient_manager->delete_transient( $meta_cache::VISIBLE_KEY );
	}

	/**
	 * Get the cache for hidden fields.
	 *
	 * @return array
	 * @since 2.8.1
	 */
	public function get_hidden_cache() {
		$meta_cache = $this->meta_cache;
		return $this->transient_manager->get_transient( $meta_cache::HIDDEN_KEY );
	}

	/**
	 * Get existing or generate a new cache for hidden meta fields,
	 * or generate a just-in-time query for non standards query limits.
	 *
	 * @param array $dummy
	 * @param int $limit
	 * @return array
	 * @since 2.8.1
	 */
	public function get_or_generate_hidden_cache( $dummy = array(), $limit = self::LIMIT ) {
		if ( self::LIMIT !== $limit ) {
			return $this->generate_hidden_query( $limit );
		}

		$cache = $this->get_hidden_cache();

		if ( false !== $cache ) {
			return $cache;
		}

		return $this->generate_hidden_cache();
	}

	/**
	 * Generate a new cache for hidden meta fields,
	 * each subclass must provide this method.
	 *
	 * @return array
	 * @since 2.8.1
	 * @since 2.8.2 Turn method non abstract and move the abstract part to generate_hidden_query.
	 */
	public function generate_hidden_cache() {
		$meta_keys = $this->generate_hidden_query( self::LIMIT );
		$this->set_hidden_cache( $meta_keys );
		return $meta_keys;
	}

	/**
	 * Generate a new query for hidden meta fields,
	 * each subclass must provide this method.
	 *
	 * @param int $limit
	 * @return array
	 * @since 2.8.2
	 */
	abstract public function generate_hidden_query( $limit = self::LIMIT );

	/**
	 * Set the cache for hidden fields.
	 *
	 * @param mixed $cache
	 * @return bool
	 * @since 2.8.1
	 */
	public function set_hidden_cache( $cache ) {
		$meta_cache = $this->meta_cache;
		return $this->transient_manager->set_transient( $meta_cache::HIDDEN_KEY, $cache );
	}

	/**
	 * Delete the cache for hidden fields.
	 *
	 * @return bool
	 * @since 2.8.1
	 */
	public function delete_hidden_cache() {
		$meta_cache = $this->meta_cache;
		return $this->transient_manager->delete_transient( $meta_cache::HIDDEN_KEY );
	}

}
