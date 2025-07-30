<?php

namespace OTGS\Toolset\Views\Controller\Cache\Meta;

use OTGS\Toolset\Views\Model\Wordpress\Transient;
use OTGS\Toolset\Views\Controller\Cache\Meta\Base;

/**
 * Abstract base for the invalidator controllers for post/term/user meta keys cache.
 *
 * @since 2.8.1
 */
abstract class InvalidatorBase {

	/**
	 * Each subclass implementing this parent will provide a dedicated pair of hooks
	 * to delete its own cache.
	 */
	const FORCE_DELETE_ACTION = '';
	const TYPES_GROUP_UPDATED_ACTION = '';

	/**
	 * @var bool
	 */
	protected $delete_visible_transient_flag = false;

	/**
	 * @var bool
	 */
	protected $delete_hidden_transient_flag = false;

	/**
	 * @var Transient
	 */
	protected $transient_manager = null;

	/**
	 * @var Base
	 */
	protected $meta_cache = null;

	/**
	 * Constructor
	 *
	 * @param Transient $transient_manager
	 * @since 2.8.1
	 */
	public function __construct( Transient $transient_manager ) {
		$this->transient_manager = $transient_manager;
	}

	/**
	 * Initialize this controler.
	 *
	 * @param Base $meta_cache
	 * @return void
	 * @since 2.8.1
	 */
	public function initialize( Base $meta_cache ) {
		$this->meta_cache = $meta_cache;

		// Register the hooks to delete the cache manually
		add_action( 'wpv_action_wpv_delete_meta_transients', array( $this, 'delete_transients' ) );
		add_action( static::FORCE_DELETE_ACTION, array( $this, 'delete_transients' ) );

		// Register the hooks that will update the existing cache when a new field arrives
		$this->add_update_hooks();

		// Check whether hooks for automatic cache invalidation need to be set
		$plugin_settings = \WPV_settings::get_instance();
		if ( 'manual' === $plugin_settings->manage_meta_transient_method ) {
			return;
		}

		// Register hooks to invalidate the cache on some Types actions
		add_action( static::TYPES_GROUP_UPDATED_ACTION, array( $this, 'force_delete_transients_flags' ) );

		// Do the actual cache invalidation, on shutdown
		add_action( 'shutdown', array( $this, 'maybe_delete_transients' ) );
	}

	/**
	 * Add the right update hooks depending on the meta domain.
	 *
	 * @since 2.8.1
	 */
	abstract protected function add_update_hooks();

	/**
	 * Decide whether the meta cache needs to be updated.
	 *
	 * @param int $meta_id ID of updated metadata entry.
	 * @param int $object_id Object ID.
	 * @param string $meta_key Meta key.
	 * @param mixed $meta_value Meta value.
	 * @since 2.8.1
	 */
	public function maybe_update_transient( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( $this->meta_cache->field_is_visible( $meta_key ) ) {
			$this->maybe_update_visible_transient( $meta_key );
		} else {
			$this->maybe_update_hidden_transient( $meta_key );
		}
	}

	/**
	 * Decide whether the meta cache for visible fields needs to be updated.
	 *
	 * @param string $meta_key Meta key.
	 * @since 2.8.1
	 */
	protected function maybe_update_visible_transient( $meta_key ) {
		$excluded_visible = $this->meta_cache->get_excluded_visible();
		if ( in_array( $meta_key, $excluded_visible, true ) ) {
			return;
		}

		$meta_cache = $this->meta_cache;

		$transient = $this->transient_manager->get_transient( $meta_cache::VISIBLE_KEY );

		if ( false === $transient ) {
			return;
		}

		if ( in_array( $meta_key, $transient, true ) ) {
			return;
		}

		$transient[] = $meta_key;

		$this->transient_manager->set_transient( $meta_cache::VISIBLE_KEY, $transient );
	}

	/**
	 * Decide whether the meta cache for hidden fields needs to be updated.
	 *
	 * @param string $meta_key Meta key.
	 * @since 2.8.1
	 */
	protected function maybe_update_hidden_transient( $meta_key ) {
		$excluded_hidden = $this->meta_cache->get_excluded_hidden();
		if ( in_array( $meta_key, $excluded_hidden, true ) ) {
			return;
		}

		$meta_cache = $this->meta_cache;

		$transient = $this->transient_manager->get_transient( $meta_cache::HIDDEN_KEY );

		if ( false === $transient ) {
			return;
		}

		if ( in_array( $meta_key, $transient, true ) ) {
			return;
		}

		$transient[] = $meta_key;

		$this->transient_manager->set_transient( $meta_cache::HIDDEN_KEY, $transient );
	}

	/**
	 * Force set the flags to delete the cache for visible and hidden meta keys.
	 *
	 * @since 2.8.1
	 */
	public function force_delete_transients_flags() {
		$this->delete_visible_transient_flag = true;
		$this->delete_hidden_transient_flag = true;
	}

	/**
	 * Check whether any cache will be actually deleted, and do it.
	 *
	 * @since 2.8.1
	 */
	public function maybe_delete_transients() {
		if ( $this->delete_visible_transient_flag ) {
			$this->delete_visible_transient();
		}
		if ( $this->delete_hidden_transient_flag ) {
			$this->delete_hidden_transient();
		}
	}

	/**
	 * Delete the cache for both visible and hidden meta fields.
	 *
	 * @since 2.8.1
	 */
	public function delete_transients() {
		$this->delete_visible_transient();
		$this->delete_hidden_transient();
	}

	/**
	 * Delete the cache for visible meta fields.
	 *
	 * @since 2.8.1
	 */
	protected function delete_visible_transient() {
		$meta_cache = $this->meta_cache;
		$this->transient_manager->delete_transient( $meta_cache::VISIBLE_KEY );
	}

	/**
	 * Delete the cache for hidden meta fields.
	 *
	 * @since 2.8.1
	 */
	protected function delete_hidden_transient() {
		$meta_cache = $this->meta_cache;
		$this->transient_manager->delete_transient( $meta_cache::HIDDEN_KEY );
	}

}
