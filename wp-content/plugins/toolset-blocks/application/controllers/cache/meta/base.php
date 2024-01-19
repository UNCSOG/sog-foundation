<?php

namespace OTGS\Toolset\Views\Controller\Cache\Meta;

/**
 * Meta cache controller: base class for post/term/user meta cache managers.
 *
 * Views keeps a cache for post/term/user meta keys, for internal usage,
 * and aims to keep it always fresh by updating it as new meta kets
 * are registered in the site.
 *
 * This cache separates between public and hidden fields, depending
 * on whether a meta key starts with an underscore, '_';
 * however, hidden fields can be declared as public to be managed and included
 * in key Views functionalities, like Views sorting options, and query filters.
 *
 * @since 2.8.1
 */
class Base {

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\Meta\ManagerBase
	 */
	protected $manager;

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\Meta\\InvalidatorBase
	 */
	protected $invalidator;

	/**
	 * @var \Toolset_Field_Definition_Factory
	 */
	protected $field_definition_factory;

	/**
	 * List of visible fields to exclude for the cache.
	 * Each children should set its own list.
	 *
	 * @var array
	 */
	protected $excluded_visible = array();

	/**
	 * List of hidden fields to exclude for the cache.
	 * Each children should set its own list.
	 *
	 * @var array
	 */
	protected $excluded_hidden = array();

	/**
	 * List of hidden fields to manage as visible.
	 *
	 * @var null|array
	 */
	public $hidden_turned_visible = null;

	/**
	 * List of Types meta keys.
	 *
	 * @var null\array
	 */
	public $types_meta_keys = null;

	/**
	 * Initialize the controller:
	 * - initialize the cache manager.
	 * - initialize the cache invalidator.
	 *
	 * @since 2.8.1
	 */
	public function initialize() {
		$this->manager->initialize( $this );
		$this->invalidator->initialize( $this );
	}

	/**
	 * Get the visible fields that should be excluded.
	 *
	 * @return array
	 * @since 2.8.1
	 */
	public function get_excluded_visible() {
		return $this->excluded_visible;
	}

	/**
	 * Get the visible fields that should be excluded.
	 *
	 * @return array
	 * @since 2.8.1
	 */
	public function get_excluded_hidden() {
		return $this->excluded_hidden;
	}

	/**
	 * Get the list of hidden custom fields to manage as visible.
	 *
	 * @return array
	 * @since 2.8.1
	 */
	public function get_hidden_turned_visible() {
		if ( null !== $this->hidden_turned_visible ) {
			return $this->hidden_turned_visible;
		}

		$this->hidden_turned_visible = array();

		return $this->hidden_turned_visible;
	}

	/**
	 * Get the Types fields keys.
	 *
	 * @return array
	 * @since 2.8.1
	 */
	public function get_types_meta_keys() {
		if ( null !== $this->types_meta_keys ) {
			return $this->types_meta_keys;
		}

		$this->types_meta_keys = array();

		return $this->types_meta_keys;
	}

	/**
	 * Auxiliar method to decide whether a field key is visible.
	 *
	 * @param string $meta_key
	 * @return bool
	 * @since 2.8.1
	 */
	public function field_is_visible( $meta_key ) {
		$whitelist = $this->get_hidden_turned_visible();
		return ( substr( $meta_key, 0, 1 ) !== '_' || in_array( $meta_key, $whitelist, true ) );
	}

	/**
	 * Auxiliar method to decide whether a field key is hidden.
	 *
	 * @param string $meta_key
	 * @return bool
	 * @since 2.8.1
	 */
	public function field_is_hidden( $meta_key ) {
		return substr( $meta_key, 0, 1 ) === '_';
	}

}
