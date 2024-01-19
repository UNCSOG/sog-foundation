<?php

namespace OTGS\Toolset\Access\Models;

/**
 * Common method for GUI
 *
 * Class GuiCommon
 *
 * @since 2.8.4
 */
class GuiCommon {

	/**
	 * @var GuiCommon
	 */
	private static $instance;

	/**
	 * @var \Toolset_Condition_Plugin_Layouts_Active|null
	 */
	private $layouts_condition;


	/**
	 * @return GuiCommon
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Initialization
	 */
	public static function initialize() {
		self::get_instance();
	}


	/**
	 * GuiCommon constructor.
	 *
	 * @param null $layouts_conditionZ
	 */
	public function __construct( $layouts_condition = null ) {
		$this->layouts_condition = ( $layouts_condition ? $layouts_condition
			: new \Toolset_Condition_Plugin_Layouts_Active() );
	}


	/**
	 * Get and cache list of Layouts
	 *
	 * @return array|bool
	 */
	public function get_layouts_list() {
		if ( ! $this->layouts_condition->is_met() ) {
			return array();
		}
		$cached_layouts_list = \Access_Cacher::get( 'layouts_available' );
		if ( false === $cached_layouts_list ) {
			$layouts_settings = \WPDD_Utils::get_all_published_settings_as_array();

			$cached_layouts_list = array();

			for ( $i = 0, $total_layouts = count( $layouts_settings ); $i < $total_layouts; $i ++ ) {
				$layout = $layouts_settings[ $i ];
				if ( isset( $layout->has_child ) && $layout->has_child === true ) {
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
	 * @param string $layout_name
	 * @param int $action
	 *
	 * @return mixed|string
	 */
	public function get_layout_slug( $layout_name, $action = 0 ) {
		if ( ! $this->layouts_condition->is_met() ) {
			return;
		}
		$cached_layouts_list = $this->get_layouts_list();

		if ( $action == 1 ) {
			foreach ( $cached_layouts_list as $layout_key => $layout_data ) {
				if ( $layout_data['post_name'] == $layout_name ) {
					return $layout_key;
				}
			}

			return '';
		}
		if ( isset( $cached_layouts_list[ $layout_name ] ) ) {
			return $cached_layouts_list[ $layout_name ]['post_name'];
		}

		return '';
	}


	/**
	 * @param string $cap
	 * @param array $role
	 *
	 * @return bool
	 */
	public function check_for_cap( $cap, $role ) {
		if ( isset( $role['capabilities'][ $cap ] ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Get and cache list of available content templates
	 *
	 * @return bool|array
	 * @since 2.2.4
	 *
	 */
	public function get_content_template_list() {
		global $wpdb;
		$cached_content_template_list = \Access_Cacher::get( 'content_templates_available' );

		if ( false === $cached_content_template_list ) {
			$available_content_template_list = $wpdb->get_results( "SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE post_type = 'view-template' AND post_status = 'publish'" );
			$cached_content_template_list = array();
			foreach ( $available_content_template_list as $template_to_cache ) {
				$cached_content_template_list[ $template_to_cache->ID ] = array(
					'post_title' => $template_to_cache->post_title,
					'post_name' => $template_to_cache->post_name,
				);
			}
			\Access_Cacher::set( 'content_templates_available', $cached_content_template_list );
		}

		return $cached_content_template_list;
	}


	/**
	 * Get Content Template title
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function get_content_template_name( $id ) {
		$cached_content_template_list = $this->get_content_template_list();
		if ( isset( $cached_content_template_list[ $id ] ) ) {
			return $cached_content_template_list[ $id ]['post_title'];
		}

		return '';
	}


	/**
	 * Get Content Template title
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function get_view_name( $id ) {
		$view = get_post( $id );
		if ( is_object( $view ) ) {
			return $view->post_title;
		}

		return '';
	}


	/**
	 * @param string $capability_to_check
	 * @param string $type
	 *
	 * @return string
	 */
	public function get_content_template_slug( $capability_to_check, $type = '' ) {
		$cached_content_template_list = $this->get_content_template_list();
		if ( $type == 1 ) {
			foreach ( $cached_content_template_list as $ct_key => $ct_data ) {
				if ( $ct_data['post_name'] == $capability_to_check ) {
					return $ct_key;
				}
			}

			return '';
		}
		if ( isset( $cached_content_template_list[ $capability_to_check ] ) ) {
			return $cached_content_template_list[ $capability_to_check ]['post_name'];
		}

		return '';
	}


	/**
	 * @param int $view_id
	 * @param string $action
	 *
	 * @return string
	 */
	public function get_views_archive_slug( $view_id, $action = '' ) {
		$cached_views_archives_list = \Access_Cacher::get( 'views_archives_available' );
		if ( false === $cached_views_archives_list ) {
			$wpv_args = array(
				'post_type' => 'view',
				'posts_per_page' => - 1,
				'order' => 'ASC',
				'orderby' => 'title',
				'post_status' => 'publish',
			);
			$wpv_query = new \WP_Query( $wpv_args );
			$wpv_count_posts = $wpv_query->post_count;
			$caching_views_archives_list = array();

			if ( $wpv_count_posts > 0 ) {
				while ( $wpv_query->have_posts() ) {
					$wpv_query->the_post();
					$post_id = get_the_id();
					$post = get_post( $post_id );
					$caching_views_archives_list[ $post->ID ] = $post->post_name;
				}
				\Access_Cacher::set( 'views_archives_available', $caching_views_archives_list );
				if ( $action == 1 ) {
					foreach ( $caching_views_archives_list as $archive_key => $archive_data ) {
						if ( $archive_data == $view_id ) {
							return $archive_key;
						}
					}

					return '';
				}
				if ( isset( $caching_views_archives_list[ $view_id ] ) ) {
					return $caching_views_archives_list[ $view_id ];
				}
			} else {
				return '';
			}
		} else {
			if ( $action == 1 ) {
				foreach ( $cached_views_archives_list as $archive_key => $archive_data ) {
					if ( $archive_data == $view_id ) {
						return $archive_key;
					}
				}

				return '';
			}
			if ( isset( $cached_views_archives_list[ $view_id ] ) ) {
				return $cached_views_archives_list[ $view_id ];
			}
		}

		return '';
	}

}
