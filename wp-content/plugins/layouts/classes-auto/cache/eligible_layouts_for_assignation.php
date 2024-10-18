<?php

namespace OTGS\Toolset\Layouts\Cache;

use OTGS\Toolset\Layouts\Util\Wordpress\Transient;

/**
 * Cache the list of eligible layouts for assignation.
 *
 * For a change, we can not generate the cache here since it is
 * depending on a very large number of methods, classes and objects.
 * Cache will be used and generated (to be stored here) in
 * WPDD_PostEditPageManager::get_eligible_layouts_for_assignation
 * and here we just manage its saving, gathering and invalidation.
 *
 * @since 2.6.3
 */
class EligibleLayoutsForAssignation {

	const TRANSIENT_KEY = 'ddl_transient_elfa';

	/**
	 * Initialize this cache.
	 *
	 * @return 2.6.3
	 */
	public function initialize() {
		$this->set_api_hooks();
		$this->set_invalidate_hooks();
	}

	/**
	 * Set API hooks to gte, set, delete this cache.
	 *
	 * @since 2.6.3
	 */
	private function set_api_hooks() {
		add_filter( 'ddl_get_elfa_cache', array( $this, 'get_cache' ) );
		add_action( 'ddl_set_elfa_cache', array( $this, 'set_cache' ) );
		add_action( 'ddl_delete_elfa_cache', array( $this, 'delete_cache' ) );
	}

	/**
	 * Set the right invalidation hooks for this cache.
	 *
	 * @since 2.6.3
	 */
	private function set_invalidate_hooks() {
		add_action( 'save_post', array( $this, 'delete_published_forms_transient' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'delete_published_forms_transient' ), 10 );
	}

	/**
	 * Delete this cache on changes in the stored objects.
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 * @since 2.6.3
	 */
	public function delete_published_forms_transient( $post_id, $post = null  ) {
		if (
			! is_object( $post )
			|| ! property_exists( $post, 'post_type' )
		) {
			$post = get_post( $post_id );
		}

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( WPDDL_LAYOUTS_POST_TYPE !== $post->post_type ) {
			return;
		}

		$this->delete_cache();
	}

	/**
	 * Get the stored cache
	 *
	 * @param array $dummy
	 * @return false|array
	 * @since 2.6.3
	 */
	public function get_cache( $dummy = array() ) {
		return get_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Set the cache to store.
	 *
	 * @param array $cache
	 * @since 2.6.3
	 */
	public function set_cache( $cache = array() ) {
		set_transient( self::TRANSIENT_KEY, $cache );
	}

	/**
	 * Delete this cache.
	 *
	 * @since 2.6.3
	 */
	public function delete_cache() {
		delete_transient( self::TRANSIENT_KEY );
	}

}
