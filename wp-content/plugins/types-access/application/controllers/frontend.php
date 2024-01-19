<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Models\UserRoles;

/**
 * Class Frontend
 *
 * @package OTGS\Toolset\Access\Controllers
 * @since 2.7
 */
class Frontend {

	private static $instance;

	private $hidden_post_types;


	/**
	 * @return Frontend
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
	 * Frontend constructor.
	 */
	function __construct() {
		//Preview custom error
		//Running preview_custom_error twice to fix an issue on multi-site when WP replace updated $current_user to original
		add_action( 'wp_head', array( $this, 'preview_custom_error' ) );
		add_action( 'init', array( $this, 'preview_custom_error' ) );

		add_filter( 'pre_get_posts', array( $this, 'show_post_preview' ) );
		add_filter( 'request', array( $this, 'set_feed_permissions' ) );
		add_filter( 'wp_get_nav_menu_items', array( $this, 'set_menu_permissions' ), null, 3 );

		add_filter( 'get_previous_post_where', array( $this, 'filter_post_link_query' ), null, 5 );
		add_filter( 'get_next_post_where', array( $this, 'filter_post_link_query' ), null, 5 );

		add_shortcode( 'toolset_access', array( $this, 'create_shortcode_toolset_access' ) );
		add_filter( 'wpv_custom_inner_shortcodes', array( $this, 'string_in_custom_inner_shortcodes' ) );

		$this->hidden_post_types = array();
	}


	public function create_shortcode_toolset_access( $atts, $content ) {
		$shortcodes = \OTGS\Toolset\Access\Controllers\Shortcodes::get_instance();

		return $shortcodes->create_shortcode_toolset_access( $atts, $content );
	}


	public function string_in_custom_inner_shortcodes( $string_in_custom_inner_shortcodes ) {
		$shortcodes = \OTGS\Toolset\Access\Controllers\Shortcodes::get_instance();

		return $shortcodes->string_in_custom_inner_shortcodes( $string_in_custom_inner_shortcodes );
	}


	/**
	 * Preview custom error from wp-admin
	 */
	public function preview_custom_error() {

		if ( ! isset( $_GET['role'] ) || ! isset( $_GET['error_type'] ) || ! isset( $_GET['id'] )
			|| ! isset( $_GET['access_preview_post_type'] )
			|| ! isset( $_GET['access_preview'] ) ) {
			return;
		}

		$preview_error = \OTGS\Toolset\Access\Controllers\Actions\ErrorPreview::get_instance();
		$preview_error->preview_error();
	}


	/**
	 * @return array
	 */
	public function get_hidden_post_types() {
		return $this->hidden_post_types;
	}


	/**
	 * @param string $where
	 * @param $in_same_term
	 * @param $excluded_terms
	 * @param $taxonomy
	 * @param $post
	 *
	 * @return mixed
	 */
	public function filter_post_link_query( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		$next_prev_links = \OTGS\Toolset\Access\Controllers\Filters\NextPrevLinksPermissions::get_instance();
		$where = $next_prev_links->set_next_prev_links_permissions( $where, $in_same_term, $excluded_terms, $taxonomy, $post );

		return $where;
	}


	/**
	 * @param $items
	 * @param $menu
	 * @param $args
	 *
	 * @return mixed
	 */
	public function set_menu_permissions( $items, $menu, $args ) {
		if ( ! class_exists( '\OTGS\Toolset\Access\Controllers\Filters\MenuPermissions' ) ) {
			require_once( TACCESS_PLUGIN_PATH . '/application/controllers/filters/menu_permisions.php' );
		}
		$menu_permissions = \OTGS\Toolset\Access\Controllers\Filters\Access_Menu_Permissions::get_instance();
		$items = $menu_permissions->set_menu_permissions( $items, $menu, $args );

		return $items;
	}


	/**
	 * @param array $query
	 * @return array
	 */
	public function set_feed_permissions( $query ) {
		if ( isset( $query['feed'] ) ) {
			$feed_permissions = \OTGS\Toolset\Access\Controllers\Filters\FeedPermissions::get_instance();
			$query = $feed_permissions->set_feed_permissions( $query );
		}

		return $query;
	}


	/**
	 * @param \WP_Query $query
	 * @return \WP_Query
	 */
	public function show_post_preview( $query ) {
		if ( $query->is_main_query() && $query->is_preview() && $query->is_singular() ) {
			$preview_error = \OTGS\Toolset\Access\Controllers\Filters\ErrorPreview::get_instance();
			$query = $preview_error->show_post_preview( $query, Settings::get_instance(), UserRoles::get_instance(), $this );
		}

		return $query;
	}

}
