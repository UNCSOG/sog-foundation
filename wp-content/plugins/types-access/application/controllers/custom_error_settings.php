<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Models\Settings as Settings;
use OTGS\Toolset\Access\Models\WPMLSettings;

/**
 * Generate preview links for custom read errors
 *
 * @package OTGS\Toolset\Access\Models
 * @since 2.7
 */
class CustomErrorSettings {

	/**
	 * @var object CustomErrorSettings
	 */
	private static $instance;

	/**
	 * @return CustomErrorSettings
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Initialize class
	 */
	public static function initialize() {
		self::get_instance();
	}

	/**
	 * @param string $post_type
	 * @param string $post_section
	 *
	 * @return string
	 */
	public function get_single_cpt_preview_link( $post_type, $post_section ) {
		global $wpcf_access;
		$wpcf_access->wpml_installed = apply_filters( 'wpml_setting', false, 'setup_complete' );
		$current_languge = apply_filters( 'wpml_current_language', null );
		$access_settings = Settings::get_instance();
		$types_settings = $access_settings->get_types_settings();
		$url = '';
		if ( 'post' === $post_section ) {
			if ( ! isset( $types_settings[ $post_type ]['mode'] )
				|| 'permissions' !== $types_settings[ $post_type ]['mode'] ) {
				return 'not_managed';
			}

			if ( $wpcf_access->wpml_installed ) {
				if ( empty( $wpcf_access->active_languages ) ) {
					WPMLSettings::get_instance()->toolset_access_wpml_loaded();
				}
				// Exclude languages used in WPML Groups
				$active_languages = $wpcf_access->active_languages;
				foreach ( $types_settings as $group_slug => $group_data ) {
					if ( strpos( $group_slug, 'wpcf-wpml-group-' ) !== 0 ) {
						continue;
					}
					if ( ! post_type_exists( $group_data['post_type'] )
						|| $group_data['post_type']
						!== $post_type ) {
						continue;
					}
					if ( isset( $group_data['languages'] ) ) {
						$language_keys = array_keys( $group_data['languages'] );
						for ( $i = 0, $total_language_keys = count( $language_keys ); $i < $total_language_keys; $i ++ ) {
							if ( isset( $active_languages[ $language_keys[ $i ] ] ) ) {
								unset( $active_languages[ $language_keys[ $i ] ] );
							}
						}
					}
				}
				if ( count( $active_languages ) > 0 ) {
					$active_languages = reset( $active_languages );
					do_action( 'wpml_switch_language', $active_languages['code'] );
				} else {
					return '';
				}
			}
			$args = array(
				'post_type' => $post_type,
				'posts_per_page' => 1,
				'post_status' => 'publish',
				'meta_query' => array(//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key' => '_wpcf_access_group',
						'value' => '',
					),
					array(
						'key' => '_wpcf_access_group',
						'compare' => 'NOT EXISTS',
					),
					'relation' => 'OR',
				),
			);
			$query = new \WP_Query( $args );
		} elseif ( 'post-group' === $post_section ) {

			$args = array(
				'post_type' => 'any',
				'posts_per_page' => 1,
				'post_status' => 'publish',
				'meta_key' => '_wpcf_access_group',
				'meta_value' => $post_type,
			);
			$query = new \WP_Query( $args );

		} elseif ( 'wpml-group' === $post_section ) {
			$types_settings = $wpcf_access->settings->types;
			$group_post_type = $types_settings[ $post_type ]['post_type'];
			$group_languages = $types_settings[ $post_type ]['languages'];
			$language_keys = array_keys( $group_languages );
			for ( $i = 0, $total_language_keys = count( $language_keys ); $i < $total_language_keys; $i ++ ) {
				do_action( 'wpml_switch_language', $language_keys[ $i ] );
				$args = array(
					'post_type' => $group_post_type,
					'posts_per_page' => 1,
					'post_status' => 'publish',
					'meta_query' => array(//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key' => '_wpcf_access_group',
							'value' => '',
						),
						array(
							'key' => '_wpcf_access_group',
							'compare' => 'NOT EXISTS',
						),
						'relation' => 'OR',
					),
				);
				$query = new \WP_Query( $args );
				if ( isset( $query->posts ) && count( $query->posts ) > 0 ) {
					$i = count( $language_keys ) + 1;
				}
			}
		}

		$posts_array = isset( $query->posts ) ? $query->posts : array();

		if ( count( $posts_array ) > 0 ) {
			$url = add_query_arg( 'toolset_access_preview', 1, get_permalink( $posts_array[0] ) );
		}

		do_action( 'wpml_switch_language', $current_languge );

		return $url;
	}


	/**
	 * Scan directory for php files.
	 *
	 * @param string $dir
	 * @param array $files
	 * @param string $exclude
	 *
	 * @return array
	 */
	public function wpcf_access_scan_dir( $dir, $files = array(), $exclude = '' ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			esc_html_e( 'There are security problems. You do not have permissions.', 'wpcf-access' );
			die();
		}

		$file_list = scandir( $dir );
		foreach ( $file_list as $file ) {
			if ( '.' !== $file
				&& '..' !== $file
				&& preg_match( '/\.php/', $file )
				&& ! preg_match( '/^comments|^single|^image|^functions|^header|^footer|^page/', $file )
				&& $file !== $exclude ) {

				if ( ! is_dir( $dir . $file ) ) {
					$files[] = $dir . $file;
				} else {
					$files = self::wpcf_access_scan_dir( $dir . $file . '/', $files );
				}
			}
		}

		return $files;
	}
}
