<?php

namespace OTGS\Toolset\Access\Controllers\Filters;

use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\Settings as Settings;

/**
 * Class ErrorPreview
 *
 * @package OTGS\Toolset\Access\Controllers\Filters
 * @since 2.7
 */
class ErrorPreview {

	private static $instance;

	public $posts;

	/**
	 * @var object
	 */
	private $settings;

	/**
	 * @var object
	 */
	private $user_roles;

	/**
	 * @var object
	 */
	private $frontend_class;

	/**
	 * @var Settings
	 */
	private $access_settings;

	/**
	 * @var Capabilities
	 */
	private $access_capabilities;


	/**
	 * ErrorPreview constructor.
	 *
	 * @param Settings|null $access_settings
	 * @param Capabilities|null $access_capabilities
	 */
	public function __construct( Settings $access_settings = null, Capabilities $access_capabilities = null ) {
		$this->access_settings = $access_settings ? : Settings::get_instance();
		$this->access_capabilities = $access_capabilities ?: Capabilities::get_instance();
	}

	/**
	 * @return ErrorPreview
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function initialize() {
		self::get_instance();
	}


	/**
	 * @param object $query
	 * @param object $settings
	 * @param object $user_roles
	 * @param object $frontend_class
	 *
	 * @return mixed
	 */
	public function show_post_preview( $query, $settings, $user_roles, $frontend_class ) {

		/**
		 * filter_posts_results - save queried $post if user has preview_any permission
		 * WP by default erase $post if post is draft and user has no capability edit_posts
		 * filter_the_posts retrun $post
		 *
		 * posts_results use priority 9 to run the filter before Views
		 */
		$this->settings = $settings;
		$this->user_roles = $user_roles;
		$this->frontend_class = $frontend_class;
		add_filter( 'posts_results', array( $this, 'filter_posts_results' ), 9, 2 );
		add_filter( 'the_posts', array( $this, 'filter_the_posts' ), 10, 2 );


		return $query;
	}


	/**
	 * Check if current user can preview a post and save post object to $preview_posts
	 *
	 * @param array|null $posts
	 * @param \WP_Query $query
	 *
	 * @return array|void
	 * @global $preview_posts
	 *
	 * @since 2.4
	 */
	function filter_posts_results( $posts, $query ) {
		remove_filter( 'pre_get_posts', array( $this->frontend_class, 'wpcf_access_show_post_preview' ) );
		remove_filter( 'posts_results', array( $this, 'filter_posts_results' ), 10, 2 );

		if ( empty( $posts ) ) {
			if ( isset( $query->query['p'] ) ) {
				$posts = get_post( $query->query['p'] );
			}
			if ( empty( $posts ) ) {
				return array();
			} else {
				$posts = array( $posts );
			}
		}

		$post_id = $posts[0]->ID;

		$settings_access = $this->access_settings->get_types_settings();

		$post_type = get_post_type( $post_id );

		if ( isset( $settings_access[ $post_type ] ) && $settings_access[ $post_type ]['mode'] == 'permissions' ) {
			if ( isset( $settings_access[ $post_type ]['permissions']['read_private']['roles'] ) ) {
				if ( $this->access_capabilities
					->user_has_permission( $settings_access[ $post_type ]['permissions']['read_private']['roles'] ) ) {
					$this->posts = $posts;
				} else {
					remove_filter( 'the_posts', array( $this, 'filter_the_posts' ) );
				}
			}
		}

		return $posts;
	}


	/**
	 * @param array $posts
	 * @param \WP_Query $query
	 *
	 * @return array
	 */
	function filter_the_posts( $posts, $query ) {
		if ( ! empty( $this->posts ) ) {
			$posts = $this->posts;
			$this->posts = array();
		}

		return $posts;
	}
}
