<?php
/**
 * Invalidator manager for the Views caching mechanism.
 *
 * @package Toolset Views
 * @since 3.1
 */

namespace OTGS\Toolset\Views\Controller\Cache\Views;

use OTGS\Toolset\Views\Domain;

/**
 * Views cache invalidator.
 *
 * @since 3.1
 */
class Invalidator {

	/**
	 * Keep track of modified objects in the current request.
	 *
	 * @var array
	 */
	private $modified_objects = array(
		Domain::POSTS => array(),
		Domain::TERMS => array(),
		Domain::USERS => array(),
	);

	/**
	 * Keep track of the modified Views in the current request.
	 *
	 * @var int[] View IDs.
	 */
	private $modified_views = array();

	/**
	 * @var \OTGS\Toolset\Views\Model\Wordpress\Transient
	 */
	private $transient = null;

	public function __construct(
		\OTGS\Toolset\Views\Model\Wordpress\Transient $transient
	) {
		$this->transient = $transient;
	}

	/**
	 * Initialize this controller.
	 *
	 * @since 3.1
	 */
	public function initialize() {
		$this->register_posts_invalidation_callbacks();
		$this->register_taxonomy_invalidation_callbacks();
		$this->register_users_invalidation_callbacks();
		$this->register_views_invalidation_callbacks();
		add_action( 'shutdown', array( $this, 'should_invalidate_cache' ) );
		add_action( 'wpv_invalidate_view_cache', array( $this, 'invalidate_view_cache_action' ) );
		add_action( 'wpv_invalidate_all_views_cache', array( $this, 'invalidate_all_views_cache_action' ) );
	}

	/**
	 * Register callbacks to keep track of modified post types in the current request.
	 *
	 * @since 3.1
	 */
	private function register_posts_invalidation_callbacks() {
		add_action( 'transition_post_status', function( $new_status, $old_status, $post_object ) {
			$this->register_modified_post_type( $post_object );
		}, 10, 3 );
		add_action( 'save_post', function( $post_id, $post_object ) {
			$this->register_modified_post_type( $post_object );
		}, 10, 2 );
		add_action( 'before_delete_post', function( $post_id ) {
			$this->register_modified_post_type_by_id( $post_id );
		}, 10, 1 );

		add_action( 'added_post_meta',function( $meta_id, $post_id ) {
			$this->register_modified_post_type_by_id( $post_id );
		}, 10, 2 );
		add_action( 'updated_post_meta',function( $meta_id, $post_id ) {
			$this->register_modified_post_type_by_id( $post_id );
		}, 10, 2 );
		add_action( 'deleted_post_meta',function( $meta_id, $post_id ) {
			$this->register_modified_post_type_by_id( $post_id );
		}, 10, 2 );
	}

	/**
	 * Register as modified a post type by a given modified post ID.
	 *
	 * @param int $post_id
	 * @since 3.1
	 */
	private function register_modified_post_type_by_id( $post_id ) {
		$post_object = get_post( $post_id );
		$this->register_modified_post_type( $post_object );
	}

	/**
	 * Register as modified a post type by a given modified post object.
	 *
	 * @param \WP_post $post_object
	 * @since 3.1
	 */
	private function register_modified_post_type( $post_object ) {
		if ( null === $post_object ) {
			return;
		}

		$this->modified_objects[ Domain::POSTS ][] = $post_object->post_type;
		// Make sure to cover those items which depend on any post type.
		$this->modified_objects[ Domain::POSTS ][] = 'any';
	}

	/**
	 * Register callbacks to keep track of modified taxonomies in the current request.
	 *
	 * @since 3.1
	 */
	private function register_taxonomy_invalidation_callbacks() {
		add_action( 'create_term', function( $term_id, $tt_id, $taxonomy ) {
			$this->modified_objects[ Domain::TERMS ][] = $taxonomy;
		}, 10, 3 );
		add_action( 'edit_terms', function( $term_id, $taxonomy ) {
			$this->modified_objects[ Domain::TERMS ][] = $taxonomy;
		}, 10, 2 );
		add_action( 'delete_term', function( $term_id, $tt_id, $taxonomy ) {
			$this->modified_objects[ Domain::TERMS ][] = $taxonomy;
		}, 10, 3 );
	}

	/**
	 * Register callbacks to keep track of modified user roles in the current request.
	 *
	 * @since 3.1
	 */
	private function register_users_invalidation_callbacks() {
		add_action( 'user_register', function( $user_id ) {
			$this->register_modified_user_role_by_id( $user_id );
		}, 10, 1 );
		add_action( 'profile_update', function( $user_id ) {
			$this->register_modified_user_role_by_id( $user_id );
		}, 10, 1 );
		add_action( 'delete_user', function( $user_id ) {
			$this->register_modified_user_role_by_id( $user_id );
		}, 10, 1 );

		add_action( 'added_user_meta', function( $meta_id, $user_id ) {
			$this->register_modified_user_role_by_id( $user_id );
		}, 10, 2 );
		add_action( 'updated_user_meta', function( $meta_id, $user_id ) {
			$this->register_modified_user_role_by_id( $user_id );
		}, 10, 2 );
		add_action( 'deleted_user_meta', function( $meta_id, $user_id ) {
			$this->register_modified_user_role_by_id( $user_id );
		}, 10, 2 );
	}

	/**
	 * Register as modified an user role by a given modified user ID.
	 *
	 * @param int $user_id
	 * @since 3.1
	 */
	private function register_modified_user_role_by_id( $user_id ) {
		$user = get_userdata( $user_id );

		if ( false === $user ) {
			return;
		}
		// Get all the user roles as an array.
		$user_roles = $user->roles;
		foreach ( $user_roles as $role ) {
			$this->modified_objects[ Domain::USERS ][] = $role;
		}
	}

	/**
	 * Register callbacks to keep track of modified Views in the current request.
	 *
	 * @since 3.1
	 */
	private function register_views_invalidation_callbacks() {
		add_action( 'wpv_action_wpv_save_item', function( $object_id ) {
			$this->modified_views[] = $object_id;
		}, 10, 1 );
		add_action( 'wpv_action_wpv_import_item', function( $object_id ) {
			$this->modified_views[] = $object_id;
		}, 10, 1 );
	}

	/**
	 * Check whether there are modified objects in this request that require invalidation of cache pieces.
	 *
	 * @since 3.1
	 */
	public function should_invalidate_cache() {
		if (
			empty( $this->modified_objects[ Domain::POSTS ] )
			&& empty( $this->modified_objects[ Domain::TERMS ] )
			&& empty( $this->modified_objects[ Domain::USERS ] )
			&& empty( $this->modified_views )
		) {
			return;
		}

		$index = get_option( Store::INDEX, array() );
		if ( empty( $index ) ) {
			return;
		}

		$domains = Domain::all();
		foreach ( $domains as $domain ) {
			$index = $this->invalidate_cache_per_domain( $domain, $index );
		}

		$index = $this->invalidate_cache_per_views( $this->modified_views, $index );

		update_option( Store::INDEX, $index );
	}

	/**
	 * Callback for the custom action to invalidate only the cache for a single given View or WPA.
	 *
	 * @param int $object_id
	 * @since 3.1
	 */
	public function invalidate_view_cache_action( $object_id ) {
		$index = get_option( Store::INDEX, array() );

		$index = $this->invalidate_cache_per_views( array( $object_id ), $index );

		update_option( Store::INDEX, $index );
	}

	/**
	 * Invalidate the frontend cache for all Views, regardless which has been modified and when.
	 *
	 * @since 3.2
	 */
	public function invalidate_all_views_cache_action() {
		$index = get_option( Store::INDEX, array() );

		$domains = Domain::all();
		foreach ( $domains as $domain ) {
			$index = $this->invalidate_all_cache_per_domain( $domain, $index );
		}

		update_option( Store::INDEX, $index );
	}

	/**
	 * Invalidate the cache for Views or WPAs by the domain of data they return.
	 * Note that this only takes care of checking against post types/taxonomies/user roles modified
	 * in the current request.
	 *
	 * @param string $domain Domain to chck against
	 * @param array $index Cache index
	 * @return array Index after getting cleared from expired cache pieces
	 * @since 3.1
	 */
	private function invalidate_cache_per_domain( $domain, $index ) {
		if ( empty( $this->modified_objects[ $domain ] ) ) {
			return $index;
		}

		$index_by_domain = toolset_getarr( $index, $domain, array() );
		$index_by_domain_clean = array();

		foreach ( $index_by_domain as $object_id => $affected_slugs ) {
			$has_matching_slugs = array_intersect( $affected_slugs, $this->modified_objects[ $domain ] );
			if ( false === empty( $has_matching_slugs ) ) {
				$this->invalidate_view_cache( $object_id );
			} else {
				$index_by_domain_clean[ $object_id ] = $affected_slugs;
			}
		}

		$index[ $domain ] = $index_by_domain_clean;

		return $index;
	}

	/**
	 * Invalidate the cache for all Views that belong to a domain.
	 *
	 * @param string $domain
	 * @param array $index
	 * @return array
	 * @since 3.2
	 */
	private function invalidate_all_cache_per_domain( $domain, $index ) {
		$index_by_domain = toolset_getarr( $index, $domain, array() );

		foreach ( $index_by_domain as $object_id => $affected_slugs ) {
			$this->invalidate_view_cache( $object_id );
		}

		$index[ $domain ] = array();

		return $index;
	}

	/**
	 * Invalidate the cache for a list of Views or WPAs IDs.
	 * This is used when clearing the cache of modified Views in this request,
	 * as well as within the callback to the action for clearing the cache for a single item.
	 *
	 * @param int[] $object_ids List of IDs to clean their cached outcome.
	 * @param array $index Cache index.
	 * @return array Index after getting cleared from expired cache pieces.
	 * @since 3.1
	 */
	private function invalidate_cache_per_views( $object_ids, $index ) {
		foreach ( $object_ids as $object_id ) {
			$this->invalidate_view_cache( $object_id );
		}

		$domains = Domain::all();
		foreach ( $domains as $domain ) {
			$index_by_domain = toolset_getarr( $index, $domain, array() );
			$index_by_domain_clean = array_filter( $index_by_domain, function( $affected_slugs, $id ) use ( $object_ids ) {
				return ( ! in_array( $id, $object_ids, false ) );
			}, ARRAY_FILTER_USE_BOTH );
			$index[ $domain ] = $index_by_domain_clean;
		}

		return $index;
	}

	/**
	 * Invalidate the transient cache for a given View or WPA ID.
	 *
	 * @param int $object_id
	 * @since 3.1
	 */
	private function invalidate_view_cache( $object_id ) {
		$this->transient->delete_transient( Store::FULL_PREFIX . $object_id );
		$this->transient->delete_transient( Store::FORM_PREFIX . $object_id );
		$this->transient->delete_transient( Store::LOOP_PREFIX . $object_id );
	}

}
