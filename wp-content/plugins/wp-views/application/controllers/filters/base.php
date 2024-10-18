<?php

/**
 * Base class for Views filters, including query filters and the frontend search filters that they might have.
 *
 * Provides basic methods for shared routines and data that every filter will need.
 *
 * @since m2m
 */
class WPV_Filter_Base {

	/**
	 * @var object|null
	 */
	protected $gui = null;

	/**
	 * @var object|null
	 */
	protected $query = null;

	/**
	 * @var object|null
	 */
	protected $search = null;

	/**
	 * @var array|null
	 */
	protected $filter_data = array();

	/**
	 * Get the GUI component.
	 *
	 * @since m2m
	 */
	public function get_gui() {
		return $this->gui;
	}

	/**
	 * Get the query component.
	 *
	 * @since m2m
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Get the search component.
	 *
	 * @since m2m
	 */
	public function get_search() {
		return $this->search;
	}

	/**
	 * Get the post types displayed by the Views providing the settings.
	 *
	 * @since m2m
	 */
	private function get_returned_post_types_in_view( $object_settings ) {
		return $object_settings['post_type'];
	}

	/**
	 * Get the post types displayed by the WordPress Archive being edited.
	 *
	 * @since m2m
	 */
	private function get_returned_post_types_in_wordpress_archive_backend() {
		if (
			! is_admin()
			|| 'view-archives-editor' != toolset_getget( 'page' )
		) {
			return array();
		}

		$wpa_id = toolset_getget( 'view_id' );

		if (
			! is_numeric( $wpa_id )
			|| intval( $wpa_id ) < 1
		) {
			return array();
		}

		$returned_post_types = array();

		$stored_settings = WPV_Settings::get_instance();
		$public_post_types = get_post_types( array( 'public' => true ) );
		foreach ( $public_post_types as $post_type_name ) {
			if ( $wpa_id == toolset_getarr( $stored_settings, 'view_cpt_' . $post_type_name ) ) {
				$returned_post_types[] = $post_type_name;
			}
		}

		return $returned_post_types;
	}

	/**
	 * Get the post types displayed by the WordPress Archive by taxonomy term being visited.
	 *
	 * @since m2m
	 */
	private function get_returned_post_types_in_wordpress_archive_frontend_taxonomy() {
		// In taxonomy archives, the related post types are stored in the relevant taxonomy object
		global $wp_query;
		$term = $wp_query->get_queried_object();
		if (
			$term
			&& isset( $term->taxonomy )
		) {
			$stored_settings = WPV_Settings::get_instance();
			$wpv_post_types_for_archive_loop = $stored_settings->wpv_post_types_for_archive_loop;
			$wpv_post_types_for_archive_loop[ 'taxonomy' ] =
				isset( $wpv_post_types_for_archive_loop[ 'taxonomy' ] )
				? $wpv_post_types_for_archive_loop[ 'taxonomy' ]
				: array();
			// type = taxonomy
			// name = tax slug
			if (
				isset( $wpv_post_types_for_archive_loop[ 'taxonomy' ][ $term->taxonomy ] )
				&& ! empty( $wpv_post_types_for_archive_loop[ 'taxonomy' ][ $term->taxonomy ] )
			) {
				foreach ( $wpv_post_types_for_archive_loop[ 'taxonomy' ][ $term->taxonomy ] as $included_post_type ) {
					if ( isset( $post_types[ $included_post_type ] ) ) {
						$post_types_included[] = $post_types[ $included_post_type ]->labels->name;
					}
				}
				return $wpv_post_types_for_archive_loop[ 'taxonomy' ][ $term->taxonomy ];
			} else {
				$taxonomy = get_taxonomy( $term->taxonomy );
				return $taxonomy->object_type;
			}
		}
		return array();
	}

	/**
	 * Get the post types displayed by the WordPress Archive being visited.
	 *
	 * @since m2m
	 */
	private function get_returned_post_types_in_wordpress_archive_frontend() {
		if (
			is_tax()
			|| is_category()
			|| is_tag()
		) {
			return $this->get_returned_post_types_in_wordpress_archive_frontend_taxonomy();
		}
		global $wp_query;
		$returned_post_types = $wp_query->get( 'post_type' );
		if (
			is_home()
			&& empty( $returned_post_types )
		) {
			// Home archive: WordPress does not set a proper post_type in the query,
			// but then fills it with just 'post' when executing the WP_Query::get_posts method.
			$returned_post_types = array( 'post' );
		}
		// This still can be empty on AJAX calls: default to an empty array
		if ( empty( $returned_post_types ) ) {
			$returned_post_types = array();
		}
		if ( ! is_array( $returned_post_types ) ) {
			$returned_post_types = array( $returned_post_types );
		}
		return $returned_post_types;
	}

	/**
	 * Get the post types displayed by the WordPress Archive providing the settings.
	 *
	 * @since m2m
	 */
	private function get_returned_post_types_in_wordpress_archive( $object_settings ) {
		if (
			is_admin()
			&& 'view-archives-editor' === toolset_getget( 'page' )
		) {
			return $this->get_returned_post_types_in_wordpress_archive_backend();
		}
		return $this->get_returned_post_types_in_wordpress_archive_frontend();
	}

	/**
	 * Get the post types displayed given a $object_settings array that belongs to a View or WordPress Archive.
	 *
	 * @param $object_settings
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	public function get_returned_post_types( $object_settings = null ) {
		$object_settings = ( null === $object_settings )
			? $this->get_current_object_settings()
			: $object_settings;

		if ( 'normal' == toolset_getarr( $object_settings, 'view-query-mode' ) ) {
			return $this->get_returned_post_types_in_view( $object_settings );
		} else {
			return $this->get_returned_post_types_in_wordpress_archive( $object_settings );
		}
	}

	/**
	 * Get the object settings for the currently rendering View or WPA.
	 *
	 * @since m2m
	 */
	public function get_current_object_settings() {
		return apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
	}

	/**
	 * Clear the currently rendering stored object settings and filter data.
	 *
	 * Filter data can be stored when a filter needs to keep its data for a period of time longer than usual.
	 *
	 * @since m2m
	 */
	public function clear_current_object_settings() {
		$this->clear_filter_data();
	}

	/**
	 * Store some filter data.
	 *
	 * Filter data can be stored when a filter needs to keep its data for a period of time longer than usual.
	 *
	 * @since m2m
	 */
	public function set_filter_data( $key, $value ) {
		$this->filter_data[ $key ] = $value;
	}

	/**
	 * Get some filter data.
	 *
	 * Filter data can be stored when a filter needs to keep its data for a period of time longer than usual.
	 *
	 * @since m2m
	 */
	public function get_filter_data( $key ) {
		return toolset_getarr( $this->filter_data, $key, null );
	}

	/**
	 * Clear the filter data.
	 *
	 * Filter data can be stored when a filter needs to keep its data for a period of time longer than usual.
	 *
	 * @since m2m
	 */
	public function clear_filter_data() {
		$this->filter_data = array();
	}

}