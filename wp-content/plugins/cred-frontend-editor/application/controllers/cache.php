<?php

namespace OTGS\Toolset\CRED\Controller;

/**
 * Caching system for Toolset Forms
 *
 * Currently, used to invalidate the cache of the known, published Toolset Forms,
 * used by the Toolset Forms shortcode generator.
 *
 * @since 1.9.3
 */
class Cache {
	
	/**
	 * @var \OTGS\Toolset\CRED\Model\Cache\Forms\Factory 
	 */
	private $cache_factory;

	/**
	 * Cache constructor.
	 *
	 * @param \OTGS\Toolset\CRED\Model\Cache\Forms\Factory $cache_factory
	 */
	public function __construct( \OTGS\Toolset\CRED\Model\Cache\Forms\Factory $cache_factory ) {
		$this->cache_factory = $cache_factory;
	}

	/**
	 * Add hooks
	 */
	public function initialize() {
		
		add_action( 'save_post', array( $this, 'delete_published_forms_transient' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'delete_published_forms_transient' ), 10 );
		
		add_action( 'user_register', array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'profile_update', array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'delete_user', array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'added_user_meta', array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'updated_user_meta', array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'deleted_user_meta', array( $this, 'delete_transient_usermeta_keys' ) );
		
		add_action( 'types_fields_group_saved', array( $this, 'delete_transient_usermeta_keys' ) );
		
		add_action( 'wpcf_save_group', array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'wpcf_group_updated', array( $this, 'delete_transient_usermeta_keys' ) );
		
	}
	
	/**
	 * Invalidate cred_transient_published_*** cache when:
	 * 	creating, updating or deleting a post form
	 * 	creating, updating or deleting an user form
	 *
	 *
	 * @since 1.9.3
	 */
	public function delete_published_forms_transient( $post_id, $post = null  ) {
		if ( ! is_object( $post ) || ! property_exists( $post, 'post_type')) {
			if ( ! $post = get_post( $post_id ) ) {
				return;
			}
		}

		try {
			if ( $caching = $this->cache_factory->create_by_post_type( $post->post_type ) ) {
				return $caching->delete_transient();
			}
		} catch( \Exception $exception ) {
			return;
		}
	}
	
	/**
	 * Delete the transient about usermeta keys.
	 *
	 * @since 1.9.3
	 */
	public function delete_transient_usermeta_keys() {
		delete_transient( 'cred_transient_usermeta_keys_visible512' );
		delete_transient( 'cred_transient_usermeta_keys_all512' );
	}

	/**
	 * Get a given transient by its key.
	 * 
	 * Generate it in case it is not available.
	 * Note that it always returns an array, even if the transient is missing.
	 *
	 * @param string $transient_key
	 * @return array
	 * @since 2.1.1
	 */
	public function get_transient( $transient_key ) {
		if ( ! $caching = $this->cache_factory->create_by_transient_key( $transient_key ) ) {
			return false;
		}

		return $caching->get_transient();
	}

	/**
	 * Delete a given transient on demand, by ts key.
	 *
	 * @param string $transient_key
	 * @since 2.1.1
	 */
	public function delete_transient( $transient_key ) {
		if ( $caching = $this->cache_factory->create_by_transient_key( $transient_key ) ) {
			return $caching->delete_transient();
		}
	}
	
}