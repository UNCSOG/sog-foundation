<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Models\Settings;

/**
 * Handles the author selector in legacy and block editors.
 *
 * Those selectors need to adjust their options,
 * based on the post type and the Access settings for publish permissions.
 */
class EditorAuthorSelector {

	const PATH_POST_NEW = 'post-new.php';
	const PATH_POST_EDIT = 'post.php';
	const PATH_POST_QUICK_EDIT = 'edit.php';

	const QUERY_ARG_ACTION = 'action';
	const QUERY_ARG_POST_TYPE = 'post_type';
	const QUERY_ARG_POST_ID = 'post';

	const DEFAULT_POST_TYPE = 'post';

	const MODE_NOT_MANAGED = 'not_managed';

	/** @var Settings */
	private $access_settings;

	/**
	 * @param Settings|null $access_settings_di
	 */
	public function __construct(
		$access_settings_di = null
	) {
		$this->access_settings = $access_settings_di;
	}

	/**
	 * Gather the stord ACcess settings JIT.
	 *
	 * @return Settings
	 */
	private function get_access_settings() {
		if ( null === $this->access_settings ) {
			$this->access_settings = Settings::get_instance();
		}
		return $this->access_settings;
	}

	/**
	 * Setup hooks.
	 */
	public function initialize() {
		add_filter( 'wp_dropdown_users_args', [ $this, 'manage_legacy_editor' ], 10, 2 );
		add_filter( 'rest_user_query', [ $this, 'manage_block_editor' ] );
	}

	/**
	 * Manage the author dropdown in legacy editors.
	 *
	 * @param array $prepared_args
	 * @param array $parsed_args The arguments passed to wp_dropdown_users() combined with the defaults.
	 * @return array
	 */
	public function manage_legacy_editor( $prepared_args, $parsed_args ) {
		if (
			'authors' !== toolset_getarr( $prepared_args, 'who' ) &&
			'post_author_override' !== toolset_getarr( $parsed_args, 'name' ) &&
			'post_author' !== toolset_getarr( $parsed_args, 'name' )
		) {
			return $prepared_args;
		}

		global $pagenow;

		if (
			self::PATH_POST_NEW === $pagenow ||
			self::PATH_POST_QUICK_EDIT === $pagenow
		) {
			$post_type = toolset_getget( self::QUERY_ARG_POST_TYPE, self::DEFAULT_POST_TYPE );
			return $this->manage_args_for_post_type( $prepared_args, $post_type );
		}

		if (
			self::PATH_POST_EDIT === $pagenow
			&& 'edit' === toolset_getget( self::QUERY_ARG_ACTION )
			&& false !== toolset_getget( self::QUERY_ARG_POST_ID, false )
		) {
			$post_type = get_post_type( (int) toolset_getget( self::QUERY_ARG_POST_ID ) );
			return $this->manage_args_for_post_type( $prepared_args, $post_type );
		}

		return $prepared_args;
	}

	/**
	 * Manage the author dropdown in block editors.
	 *
	 * @param mixed[] $prepared_args
	 * @return mixed[]
	 */
	public function manage_block_editor( $prepared_args ) {
		if ( 'authors' !== toolset_getarr( $prepared_args, 'who' ) ) {
			return $prepared_args;
		}

		$parsed_referrer_url = wp_parse_url( wp_get_referer() );

		if ( false === $parsed_referrer_url ) {
			return $prepared_args;
		}

		$path = toolset_getarr( $parsed_referrer_url, 'path', '' );
		$query = toolset_getarr( $parsed_referrer_url, 'query', '' );
		parse_str( $query, $query_args );

		$post_type = false;

		if ( false !== strpos( $path, self::PATH_POST_NEW ) ) {
			$post_type = toolset_getarr( $query_args, self::QUERY_ARG_POST_TYPE, self::DEFAULT_POST_TYPE );
			return $this->manage_args_for_post_type( $prepared_args, $post_type );
		}

		if (
			false !== strpos( $path, self::PATH_POST_EDIT )
			&& 'edit' === toolset_getarr( $query_args, self::QUERY_ARG_ACTION )
			&& false !== toolset_getarr( $query_args, self::QUERY_ARG_POST_ID, false )
		) {
			$post_type = get_post_type( (int) toolset_getarr( $query_args, self::QUERY_ARG_POST_ID ) );
			return $this->manage_args_for_post_type( $prepared_args, $post_type );
		}

		return $prepared_args;
	}

	/**
	 * Adjust the roles able to author posts for the affected post type.
	 *
	 * @param mixed[] $prepared_args
	 * @param string|false $post_type
	 * @return mixed[]
	 */
	private function manage_args_for_post_type( $prepared_args, $post_type ) {
		if ( false === $post_type ) {
			return $prepared_args;
		}

		$access_settings = $this->get_access_settings();

		$post_types_settings = $access_settings->get_types_settings( true, true );
		$post_types_settings = toolset_ensarr( $post_types_settings );

		$management_mode = toolset_getnest( $post_types_settings, [ $post_type, 'mode' ], 'not_managed' );

		if ( 'follow' === $management_mode ) {
			$post_type = self::DEFAULT_POST_TYPE;
			$management_mode = toolset_getnest( $post_types_settings, [ $post_type, 'mode' ], 'not_managed' );
		}

		if ( 'not_managed' === $management_mode ) {
			return $prepared_args;
		}

		$publishing_roles = toolset_getnest( $post_types_settings, [ $post_type, 'permissions', 'publish', 'roles' ], [] );

		$prepared_args['who'] = '';
		$prepared_args['role__in'] = array_merge( array('administrator'), $publishing_roles );

		if (
			version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) &&
			array_key_exists( 'capability', $prepared_args )
		) {
			unset( $prepared_args['capability'] );
		}

		return $prepared_args;
	}
}
