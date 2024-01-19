<?php

namespace OTGS\Toolset\Access\Controllers\Actions;

use OTGS\Toolset\Access\Controllers\Frontend;
use OTGS\Toolset\Access\Controllers\CustomErrors;

/**
 * A class collect Front-end actions methods
 *
 * Class FrontendActions
 *
 * @package OTGS\Toolset\Access\Controllers\Actions
 * @since 2.7
 */
class FrontendActions {

	/**
	 * @var FrontendActions
	 */
	private static $instance;

	/**
	 * @var string
	 */
	public $error;


	/**
	 * @return FrontendActions
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
	 * @return string
	 */
	public function get_archive_post_type() {
		global $wp_query;

		$post_type_object = $wp_query->get_queried_object();
		if ( $post_type_object && ! empty( $post_type_object->name ) ) {
			return $post_type_object->name;
		}
		if ( is_archive() || is_home() || is_search() ) {
			return 'post';
		}

		return '';
	}
	/**
	 * Override existing WPA for post type
	 *
	 * @param int $view
	 *
	 * @return bool
	 */
	public function toolset_access_replace_archive_view( $view ) {
		$post_type = $this->get_archive_post_type();
		if ( ! empty( $post_type ) ) {
			$error = \Access_Cacher::get( 'wpcf_archive_error_value_' . $post_type );
			if ( false !== $error ) {
				$view = $error;
				\Access_Cacher::delete( 'wpcf_archive_error_value_' . $post_type );
			}
		}

		return $view;
	}


	/**
	 * Load php file on Archive pages
	 */
	public function toolset_access_replace_archive_php_template() {
		$post_type = $this->get_archive_post_type();

		if ( ! empty( $post_type ) ) {
			$this->error = \Access_Cacher::get( 'wpcf_archive_error_value_' . $post_type );
			if ( false !== $this->error ) {
				$template = $this->error;
				if ( file_exists( $template ) ) {
					include $template;
					\Access_Cacher::delete( 'wpcf_archive_error_value_' . $post_type );
					exit;
				}
			}
		}
	}


	/**
	 * @param int $id
	 *
	 * @return int|string
	 */
	public function toolset_access_error_template_archive_layout( $id ) {
		remove_action( 'wp_head', array( $this, 'toolset_access_error_template_archive_layout' ) );

		if ( ! class_exists( 'WPDD_Layouts' ) ) {
			return '';
		}
		if ( is_tag() || is_category() || is_tax() ) {
			return $id;
		}

		if ( $this->is_layout_custom_error() ) {
			add_filter( 'get_layout_id_for_render', array( $this, 'toolset_access_load_layout_archive' ) );
		}

		return $id;
	}


	/**
	 * @return bool
	 */
	public function toolset_access_load_layout_archive_is_assigned() {
		remove_filter( 'ddl-is_ddlayout_assigned', array(
			$this,
			'toolset_access_load_layout_archive_is_assigned',
		) );
		if ( is_tag() || is_category() || is_tax() ) {
			if ( function_exists( 'is_ddlayout_assigned' ) ) {
				return is_ddlayout_assigned();
			} else {
				return false;
			}
		}
		add_filter( 'ddl-is_ddlayout_assigned', array(
			$this,
			'toolset_access_load_layout_archive_is_assigned',
		) );
		return true;
	}


	/**
	 * Return custom error layout id
	 * if layouts doesn't exists, return id of assigned layout
	 *
	 * @return mixed
	 */
	public function toolset_access_load_layout_archive() {
		remove_filter( 'get_layout_id_for_render', array( $this, 'toolset_access_load_layout_archive' ) );
		if ( $this->is_layout_custom_error() ) {
			$post_type = $this->get_archive_post_type();

			if ( ! empty( $post_type ) ) {
				$output = \Access_Cacher::get( 'wpcf_archive_error_value_' . $post_type );

				return $output;
			}
		}
		$id = \WPDD_Layouts_RenderManager::getInstance()->get_layout_id_for_render( null, array() );

		return $id;
	}


	/**
	 * @return bool
	 */
	public function is_layout_custom_error() {
		global $wp_query;
		$post_type_object = $wp_query->get_queried_object();
		$post_type = $this->get_archive_post_type();
		if ( ! empty( $post_type ) || ( $post_type_object && $post_type_object instanceof \WP_Post_Type ) ) {
			$cached_layout_id = \Access_Cacher::get( 'wpcf_archive_error_value_' . $post_type );
			if ( $cached_layout_id > 0 ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Load PHP Template error
	 *
	 * @param string $template
	 */
	public function toolset_access_error_php_template( $template ) {
		global $post;

		if ( ! isset( $post ) || empty( $post ) ) {
			return;
		}
		$post_id = $post->ID;
		$template = \Access_Cacher::get( 'wpcf-access-post-permissions-' . $post_id );
		if ( false === $template ) {
			$template = CustomErrors::get_instance()->get_custom_error( $post_id );
			\Access_Cacher::set( 'wpcf-access-post-permissions-' . $post_id, $template );
		}
		$templates = wp_get_theme()->get_page_templates();
		if ( ! empty( $templates ) ) {
			$file = '';
			foreach ( $templates as $template_name => $template_filename ) {
				if ( $template_filename === $template[1] ) {
					$file = $template_name;
				}
			}
			if ( ! empty( $file ) && file_exists( get_template_directory() . '/' . $file ) ) {
				include get_template_directory() . '/' . $file;
			} elseif ( ! empty( $file ) && file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				include get_stylesheet_directory() . '/' . $file;
			} else {
				echo '<h1>' . esc_html( __( 'Can\'t find php template', 'wpcf-access' ) ) . '</h1>';
			}
			exit;
		} else {
			return;
		}
	}


	/**
	 * @param string $content
	 *
	 * @return string|void
	 */
	public function toolset_access_error_template_layout( $content ) {
		global $post;
		remove_action( 'wp', array( $this, 'toolset_access_error_template_layout' ) );

		if ( ! class_exists( 'WPDD_Layouts' ) ) {
			return '';
		}
		if ( ! isset( $post ) || empty( $post ) ) {
			return;
		}

		$is_layout_template = has_current_post_ddlayout_template();
		$post_id = $post->ID;
		$template_info = \Access_Cacher::get( 'wpcf-access-post-permissions-' . $post_id );
		if ( false === $template_info ) {
			$template_info = CustomErrors::get_instance()->get_custom_error( $post_id );
			\Access_Cacher::set( 'wpcf-access-post-permissions-' . $post_id, $template_info );
		}
		$template = $this->get_layout_name( $template_info[1] );

		if ( empty( $template ) ) {
			return '';
		}

		\Access_Cacher::set( 'wpcf_single_post_error_value', $template_info[1] );

		add_filter( 'get_layout_id_for_render', array( $this, 'toolset_access_load_layout' ) );
		add_filter( 'ddl-get_layout_id_by_slug', array( $this, 'toolset_access_load_layout' ) );

		do_action( 'toolset_theme_settings_force_settings_refresh', $post->ID );

		if ( ! $is_layout_template ) {
			/**
			 * Ddl_apply_the_content_filter_in_cells, ddl_apply_the_content_filter_in_post_content_cell disable the_content filter for
			 * visual editor and post content cells when loading custom error assigned to layout
			 */
			add_filter( 'ddl_apply_the_content_filter_in_cells', '__return_false', 11, 1 );
			add_filter( 'ddl_apply_the_content_filter_in_post_content_cell', '__return_false', 11, 1 );
			add_filter( 'the_content', array( $this, 'toolset_access_error_template_layout_the_content' ) );
		}

	}


	/**
	 * Return layout id
	 *
	 * @return bool|int
	 */
	public function toolset_access_load_layout() {
		$output = \Access_Cacher::get( 'wpcf_single_post_error_value' );

		return $output;
	}


	/**
	 * @param string $content
	 *
	 * @return mixed
	 */
	public function toolset_access_error_template_layout_the_content( $content ) {

		remove_filter( 'the_content', array( $this, 'toolset_access_error_template_layout_the_content' ) );
		remove_filter( 'ddl_apply_the_content_filter_in_cells', '__return_false', 11 );
		remove_filter( 'ddl_apply_the_content_filter_in_post_content_cell', '__return_false', 11 );

		$error = \Access_Cacher::get( 'wpcf_single_post_error_value' );
		$_GET['layout_id'] = $error;
		add_filter( 'get_layout_id_for_render', array( $this, 'toolset_access_load_layout' ) );
		$layout_content = get_the_ddlayout( $error, array( 'initialize_loop' => false ) );

		add_filter( 'ddl_apply_the_content_filter_in_cells', '__return_false', 11, 1 );
		add_filter( 'ddl_apply_the_content_filter_in_post_content_cell', '__return_false', 11, 1 );
		add_filter( 'the_content', array( $this, 'toolset_access_error_template_layout_the_content' ) );

		return $layout_content;
	}


	/**
	 * @param int $id
	 *
	 * @return string
	 */
	public function get_layout_name( $id ) {
		$cached_layouts_list = $this->get_layouts_list();

		if ( isset( $cached_layouts_list[ $id ] ) ) {
			return $cached_layouts_list[ $id ]['post_title'];
		}

		return '';
	}


	/**
	 * Get and cache list of Layouts
	 *
	 * @return array|bool
	 */
	public static function get_layouts_list() {
		if ( ! class_exists( 'WPDD_Layouts' ) ) {
			return array();
		}
		$cached_layouts_list = \Access_Cacher::get( 'layouts_available' );
		if ( false === $cached_layouts_list ) {
			$layouts_settings = \WPDD_Utils::get_all_published_settings_as_array();

			$cached_layouts_list = array();

			for ( $i = 0, $total_layouts = count( $layouts_settings ); $i < $total_layouts; $i ++ ) {
				$layout = $layouts_settings[ $i ];
				if ( isset( $layout->has_child ) && true === $layout->has_child ) {
					continue;
				}
				$cached_layouts_list[ $layout->id ] = array(
					'post_name' => $layout->slug,
					'post_title' => $layout->name,
				);
			}
			\Access_Cacher::set( 'layouts_available', $cached_layouts_list );
		}

		return $cached_layouts_list;
	}


	/**
	 * Exclude current post from list of queries
	 *
	 * @param object $query
	 */
	public function toolset_access_exclude_selected_post_from_single( $query ) {
		if ( ! is_admin() && $query->is_main_query() ) {
			$post_id = toolset_access_get_current_page_id();
			if ( ! isset( $post_id ) || empty( $post_id ) ) {
				return;
			}
			$not_in = $query->get( 'post__not_in' );
			$not_in[] = $post_id;
			$query->set( 'post__not_in', $not_in );
		}
	}


}
