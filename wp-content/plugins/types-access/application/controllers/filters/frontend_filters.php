<?php

namespace OTGS\Toolset\Access\Controllers\Filters;

use OTGS\Toolset\Access\Controllers\CustomErrors;
use OTGS\Toolset\Access\Controllers\PermissionsRead;

/**
 * Front-end filters methods
 *
 * @package OTGS\Toolset\Access\Controllers\Filters
 * @since 2.7
 */
class FrontendFilters {

	/**
	 * @var FrontendFilters
	 */
	private static $instance;


	/**
	 * @return FrontendFilters
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Class initialization
	 */
	public static function initialize() {
		self::get_instance();
	}


	/**
	 * Filters posts.
	 *
	 * @param string $args
	 * @return string
	 */
	public function filter_posts( $args ) {
		global $wpdb;
		$permission = PermissionsRead::get_instance();
		$hidden_post_types = $permission->get_hidden_post_types();
		foreach ( $hidden_post_types as $post_type ) {
			$args .= " AND $wpdb->posts.post_type <> '$post_type'";
		}

		return $args;
	}


	/**
	 * Excludes pages if necessary.
	 *
	 * @param array $pages
	 * @return array
	 */
	public function exclude_pages( $pages ) {
		$permission = PermissionsRead::get_instance();
		$hidden_post_types = $permission->get_hidden_post_types();
		if ( in_array( 'page', $hidden_post_types, true ) ) {
			return array();
		}

		return $pages;
	}


	/**
	 * Filters comments.
	 *
	 * @param \WP_Comment[] $comments
	 * @return \WP_Comment[]
	 */
	public function filter_comments( $comments ) {
		$permission = PermissionsRead::get_instance();
		$hidden_post_types = $permission->get_hidden_post_types();
		foreach ( $comments as $key => $comment ) {
			$comment_owner_post_type = get_post_type( $comment->comment_post_ID );
			if ( in_array( $comment_owner_post_type, $hidden_post_types, true ) ) {
				unset( $comments[ $key ] );
			}
		}

		return $comments;
	}


	/**
	 * Disable comments on page where custom error - Content template
	 *
	 * @return bool
	 */
	public function toolset_access_disable_comments() {
		return false;
	}


	/**
	 * Load Content template error
	 *
	 * @param string $template_selected
	 * @param string $post_id
	 * @param string $kind
	 *
	 * @return mixed|void
	 */
	public function toolset_access_error_content_template( $template_selected, $post_id, $kind = '' ) {
		$template = \Access_Cacher::get( 'wpcf-access-post-permissions-' . $post_id );
		if ( false === $template ) {
			$custom_errors = CustomErrors::get_instance();
			$template = $custom_errors->get_custom_error( $post_id );
			\Access_Cacher::set( 'wpcf-access-post-permissions-' . $post_id, $template );
		}
		if ( isset( $template[0] ) && ! empty( $template[0] ) ) {
			return $template[1];
		} else {
			return;
		}

	}

}
