<?php
/**
 * Store for the Views caching mechanism.
 *
 * @package Toolset Views
 * @since 3.1
 */

namespace OTGS\Toolset\Views\Controller\Cache\Views;

use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

/**
 * Views cache store.
 *
 * @since 3.1
 */
class Store {

	const INDEX = 'wpv_cached_index';

	const FULL_PREFIX = 'wpv_cached_view_';
	const FORM_PREFIX = 'wpv_cached_form_';
	const LOOP_PREFIX = 'wpv_cached_loop_';

	const UNKNOWN_LANG = 'unknown';

	/**
	 * Instance of this singleton.
	 *
	 * @var \OTGS\Toolset\Views\Controller\Cache\Views\Store
	 * @since 3.1
	 */
	private static $instance = null;

	/**
	 * @var \OTGS\Toolset\Views\Model\Wordpress\Transient
	 */
	private $transient = null;

	/**
	 * @var MobileDetect
	 */
	private $mobile_detect;

	public function __construct(
		\OTGS\Toolset\Views\Model\Wordpress\Transient $transient = null,
		MobileDetect $mobile_detect = null
	) {
		$this->transient = ( null === $transient )
			? new \OTGS\Toolset\Views\Model\Wordpress\Transient()
			: $transient;

		$this->mobile_detect = $mobile_detect ?: new MobileDetect();
	}

	/**
	 * Get the instance of this controller.
	 *
	 * @return \OTGS\Toolset\Views\Controller\Cache\Views\Store
	 * @since 3.1
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the index of stored Views outcomes.
	 *
	 * @return array {
	 *     posts|taxonomy|users => array {
	 *         view_id => array List of object slugs (post type or taxonomy) or user roles that should invalidate this cache.
	 *     }
	 * }
	 * @since 3.1
	 */

	public function get_stored_index() {
		return get_option( self::INDEX, array() );
	}

	/**
	 * Set the index store.
	 *
	 * @param array $cache
	 * @since 3.1
	 */
	private function set_stored_index( $cache ) {
		update_option( self::INDEX, $cache );
	}

	/**
	 * Get the stored cache for a View or WPA given its flavour index.
	 * Note that there is a stored cache per WPML active language.
	 *
	 * @param string $prefix
	 * @param int $object_id
	 * @return string|false FALSE on missing cache
	 * @since 3.1
	 */
	private function get_stored_cache( $prefix, $object_id ) {
		if( $this->mobile_detect->isMobile() ) {
			// No caching for mobile (tablet is also mobile).
			return false;
		}

		$trasient_key  = $prefix . $object_id;
		$cache = $this->transient->get_transient( $trasient_key );

		if ( ! is_array( $cache ) ) {
			return false;
		}

		$current_language = apply_filters( 'wpml_current_language', self::UNKNOWN_LANG );

		return toolset_getarr( $cache, $current_language, false );
	}

	/**
	 * Set the stored cache for a View or WPA given its flavour index.
	 * Note that there is a stored cache per WPML active language.
	 *
	 * @param string $prefix
	 * @param int $object_id
	 * @param string $cache
	 * @since 3.1
	 */
	private function set_stored_cache( $prefix, $object_id, $cache ) {
		if( $this->mobile_detect->isMobile() ) {
			// No caching for mobile (tablet is also mobile).
			return;
		}

		$object = \WPV_View_Base::get_instance( $object_id );

		if ( null === $object ) {
			return;
		}

		$trasient_key  = $prefix . $object_id;
		$stored_cache = $this->transient->get_transient( $trasient_key );

		if (
			false === $stored_cache
			|| ! is_array( $stored_cache )
		) {
			$stored_cache = array();
		}

		$current_language = apply_filters( 'wpml_current_language', self::UNKNOWN_LANG );

		$stored_cache[ $current_language ] = $cache;
		$cache_duration = $this->get_cache_duration( $object );
		$is_cached = $this->transient->set_transient( $trasient_key, $stored_cache, $cache_duration );

		if ( false === $is_cached ) {
			return;
		}

		$index = $this->get_stored_index( $prefix );
		$query_type = $object->query_type;

		$index[ $query_type ] = array_key_exists( $query_type, $index )
			? $index[ $query_type ]
			: array();

		$object_settings = $object->view_settings;

		switch ( $query_type ) {
			case 'taxonomy':
				$index[ $query_type ][ $object_id ] = toolset_getarr( $object_settings, 'taxonomy_type' );
				break;
			case 'users':
				$index[ $query_type ][ $object_id ] = toolset_getarr( $object_settings, 'roles_type' );
				break;
			default:
				if ( \WPV_View_Base::is_archive_view( $object_id ) ) {
					$index[ $query_type ][ $object_id ] = array( 'any' );
				} else {
					$index[ $query_type ][ $object_id ] = toolset_getarr( $object_settings, 'post_type' );
				}
				break;
		}

		$this->set_stored_index( $index );
	}

	/**
	 * Calculate the duration of the cache for a given object.
	 * Note that Views containing a frontend search get a single day cache duration,
	 * while the rest of them can last up to a week.
	 *
	 * @param \WPV_View_Base $object
	 * @return int
	 * @since 3.1
	 * @todo Make Views containing query filters also get a single day cache duration.
	 */
	private function get_cache_duration( $object ) {
		$object_settings = $object->view_settings;
		$object_search_editor = toolset_getarr( $object_settings, 'filter_meta_html' );
		if (
			strpos( $object_search_editor, "[wpv-control" )
			|| strpos( $object_search_editor, "[wpv-filter-search-box" )
			|| strpos( $object_search_editor, "[wpv-filter-submit" ) )
		{
			return DAY_IN_SECONDS;
		}
		return WEEK_IN_SECONDS;
	}

	/**
	 * Get the cache for the full output for a given View or WPA.
	 *
	 * @param int $object_id
	 * @return string|false FALSE in missing cache
	 * @since 3.1
	 */
	public function get_full_cache( $object_id ) {
		return $this->get_stored_cache( self::FULL_PREFIX, $object_id );
	}

	/**
	 * Set the full cache for a given View or WPA.
	 *
	 * @param int $object_id
	 * @param string $outcome
	 * @since 3.1
	 */
	public function set_full_cache( $object_id, $outcome ) {
		$this->set_stored_cache( self::FULL_PREFIX, $object_id, $outcome );
	}

	/**
	 * Get the cache for the form output for a given View or WPA.
	 *
	 * @param int $object_id
	 * @return string|false FALSE in missing cache
	 * @since 3.1
	 */
	public function get_form_cache( $object_id ) {
		return $this->get_stored_cache( self::FORM_PREFIX, $object_id );
	}

	/**
	 * Set the form cache for a given View or WPA.
	 *
	 * @param int $object_id
	 * @param string $outcome
	 * @since 3.1
	 */
	public function set_form_cache( $object_id, $outcome ) {
		$this->set_stored_cache( self::FORM_PREFIX, $object_id, $outcome );
	}

	/**
	 * Get the cache for the loop output for a given View or WPA.
	 *
	 * @param int $object_id
	 * @return string|false FALSE in missing cache
	 * @since 3.1
	 */
	public function get_loop_cache( $object_id ) {
		return $this->get_stored_cache( self::LOOP_PREFIX, $object_id );
	}

	/**
	 * Set the loop cache for a given View or WPA.
	 *
	 * @param int $object_id
	 * @param string $outcome
	 * @since 3.1
	 */
	public function set_loop_cache( $object_id, $outcome ) {
		$this->set_stored_cache( self::LOOP_PREFIX, $object_id, $outcome );
	}

}
