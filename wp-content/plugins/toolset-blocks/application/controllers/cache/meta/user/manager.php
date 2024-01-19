<?php

namespace OTGS\Toolset\Views\Controller\Cache\Meta\User;

use OTGS\Toolset\Views\Controller\Cache\Meta\ManagerBase;

/**
 * TUsereta cache controller.
 *
 * @since 2.8.1
 */
class Manager extends ManagerBase {

	/**
	 * Register API hooks to get, set and delete the cache for usermeta fields.
	 *
	 * @since 2.8.1
	 */
	protected function add_hooks() {
		add_filter( 'wpv_get_visible_usermeta_cache', array( $this, 'get_or_generate_visible_cache' ), 10, 2 );
		add_action( 'wpv_generate_visible_usermeta_cache', array( $this, 'generate_visible_cache' ) );
		add_action( 'wpv_delete_visible_usermeta_cache', array( $this, 'delete_visible_cache' ) );

		add_filter( 'wpv_get_hidden_usermeta_cache', array( $this, 'get_or_generate_hidden_cache' ), 10, 2 );
		add_action( 'wpv_generate_hidden_usermeta_cache', array( $this, 'generate_hidden_cache' ) );
		add_action( 'wpv_delete_hidden_usermeta_cache', array( $this, 'delete_hidden_cache' ) );
	}

	/**
	 * Generate the query for visible usermeta fields.
	 *
	 * @param int $limit
	 * @return array
	 * @since 2.8.2
	 */
	public function generate_visible_query( $limit = null ) {
		$limit = ( null === $limit ) ? static::LIMIT : $limit;

		$values_to_prepare = array();
		$tmf_mulsitise_string = '';

		if ( is_multisite() ) {
			global $blog_id;
			$tmf_mulsitise_string = " AND ( meta_key NOT REGEXP '^{$this->wpdb->base_prefix}[0-9]_' OR meta_key REGEXP '^{$this->wpdb->base_prefix}%d_' ) ";
			$values_to_prepare[] = $blog_id;
		}
		$values_to_prepare[] = $limit;

		$meta_keys = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT DISTINCT meta_key
				FROM {$this->wpdb->usermeta}
				WHERE LEFT( meta_key, 1 ) <> '_' "
				. "{$tmf_mulsitise_string}
				LIMIT %d",
				$values_to_prepare
			)
		);

		$types_meta_keys = $this->meta_cache->get_types_meta_keys();
		$types_meta_keys = array_filter( $types_meta_keys, array( $this->meta_cache, 'field_is_visible' ) );

		$hidden_turned_visible = $this->meta_cache->get_hidden_turned_visible();

		$meta_keys = array_merge( $meta_keys, $types_meta_keys, $hidden_turned_visible );

		$excluded_visible = $this->meta_cache->get_excluded_visible();
		$meta_keys = array_diff( $meta_keys, $excluded_visible );

		$meta_keys = array_unique( $meta_keys );
		return $meta_keys;
	}

	/**
	 * Generate the query for hidden usermeta fields.
	 *
	 * @param int $limit
	 * @return array
	 * @since 2.8.2
	 */
	public function generate_hidden_query( $limit = null ) {
		$limit = ( null === $limit ) ? static::LIMIT : $limit;

		$values_to_prepare = array();
		$tmf_mulsitise_string = '';

		if ( is_multisite() ) {
			global $blog_id;
			$tmf_mulsitise_string = " AND ( meta_key NOT REGEXP '^{$this->wpdb->base_prefix}[0-9]_' OR meta_key REGEXP '^{$this->wpdb->base_prefix}%d_' ) ";
			$values_to_prepare[] = $blog_id;
		}
		$values_to_prepare[] = $limit;

		$meta_keys = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT DISTINCT meta_key FROM {$this->wpdb->usermeta}
				WHERE LEFT(meta_key, 1) == '_'
				{$tmf_mulsitise_string}
				LIMIT %d",
				$values_to_prepare
			)
		);

		$types_meta_keys = $this->meta_cache->get_types_meta_keys();
		$types_meta_keys = array_filter( $types_meta_keys, array( $this->meta_cache, 'field_is_hidden' ) );

		$meta_keys = array_merge( $meta_keys, $types_meta_keys );

		$excluded_hidden = $this->meta_cache->get_excluded_hidden();
		$meta_keys = array_diff( $meta_keys, $excluded_hidden );

		$meta_keys = array_unique( $meta_keys );
		return $meta_keys;
	}

}
