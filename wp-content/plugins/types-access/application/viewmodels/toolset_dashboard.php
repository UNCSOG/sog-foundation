<?php

namespace OTGS\Toolset\Access\Viewmodels;

use OTGS\Toolset\Access\Models\GuiCommon;
use OTGS\Toolset\Access\Models\Settings as Settings;
use Toolset_Condition_Plugin_Layouts_Active;
use Toolset_Condition_Plugin_Views_Active;

/**
 * Add Toolset elements used in Access to a Toolset Dashboard
 *
 * Class ToolsetDashboard
 *
 * @package OTGS\Toolset\Access\Viewmodels
 * @since 2.8.8
 */
class ToolsetDashboard {

	/**
	 * Cache group
	 */
	const CACHE_GROUP = __CLASS__;

	/**
	 * @var bool
	 */
	private $is_views_active;

	/**
	 * @var bool
	 */
	private $is_layouts_active;

	/**
	 * ToolsetDashboard constructor.
	 *
	 * @param Toolset_Condition_Plugin_Views_Active|null $is_views_active
	 * @param Toolset_Condition_Plugin_Layouts_Active|null $is_layouts_active
	 */
	public function __construct( Toolset_Condition_Plugin_Views_Active $is_views_active = null, Toolset_Condition_Plugin_Layouts_Active $is_layouts_active = null ) {
		wp_cache_add_non_persistent_groups( array( self::CACHE_GROUP ) );

		$is_views_active_class = $is_views_active ? : new Toolset_Condition_Plugin_Views_Active();
		$is_layouts_active_class = $is_layouts_active ? : new Toolset_Condition_Plugin_Layouts_Active();

		$this->is_views_active = $is_views_active_class->is_met();
		$this->is_layouts_active = $is_layouts_active_class->is_met();
	}


	/**
	 * Generates Toolset elements list HTML used in Access custom errors.
	 *
	 * @param \WP_Post_Type $post_type_object
	 * @param string $list_type
	 *
	 * @return string
	 */
	public function get_custom_errors_assigned_elements( \WP_Post_Type $post_type_object, $list_type = 'single_page_list' ) {
		$post_type = $post_type_object->name;
		$access_custom_errors_elements = $this->get_custom_errors_assigned_elements_array( $post_type );
		$elements = toolset_getnest( $access_custom_errors_elements, array( $list_type ), array() );
		$elements_list = '';
		if ( ! empty( $elements ) ) {
			foreach ( $elements as $element ) {
				$pointer_id = esc_attr( $list_type . '-' . md5( $post_type . $element['error_value'] ) );
				if ( 'error_php' === $element['error_type_slug'] ) {
					$element['error_value_converted'] = preg_replace( "/.*(\/.*\/)/", "$1", $element['error_value'] );
				}
				$elements_list .= sprintf( '<li data-types-open-pointer="%s" class="data-types-open-pointer-hover" ><a href="%s">%s</a></li>',
					$pointer_id, esc_attr( $element['edit_link'] ), esc_html( $element['error_value_converted'] ) );

				if ( in_array( 'Everyone', $element['roles'] ) ) {
					if ( empty( $element['wpml_group'] ) ) {
						$pointer_text = sprintf( __( '%s displayed by default to users without <b>read</b> permission.', 'wpcf' ), $element['error_type_title'] );
					} else {
						$pointer_text = sprintf( __( '%s displayed by default on <b>%s</b> to users without <b>read</b> permission.', 'wpcf' ), $element['error_type_title'], $element['wpml_group'] );
					}
				} else {
					$pointer_text = sprintf( __( '%s displayed to <b>%s</b> users without <b>read</b> permission.', 'wpcf' ), $element['error_type_title'],
						implode( ', ', $element['roles'] ) );
				}

				$elements_list .= $this->generate_pointer_html( $pointer_id, $pointer_text );
			}
		}

		if ( ! empty( $elements_list ) ) {
			$title = 'single_page_list' === $list_type ? __( 'Templates', 'wpcf' ) : __( 'Archives', 'wpcf-access' );
			$post_type_pointer_id = esc_attr( $list_type . '-' . md5( $post_type ) );
			$post_type_pointer_text = sprintf( __( '%s displayed for users who do not have read permission for the %s post type. This can be changed with Access Control.', 'wpcf-access' ),
				$title, $post_type_object->label );
			$elements_list = $this->generate_pointer_html( $post_type_pointer_id, $post_type_pointer_text )
				. ' <p class="types-alternative-paragraph"><i class="icon-access-logo fa fa-wpv-custom ont-icon-18 toolset-dashboard-alternative-plugin-icon" ></i><span>'
				. __( 'Alternative ', 'wpcf-access' ) . $title
				. '</span>
				<span style="color:#0073aa" id="types-pointer-target-template1" ' .
				'class="dashicons dashicons-editor-help data-types-open-pointer-hover toolset-dashboard-alternative-help-icon" data-types-open-pointer="'
				. $post_type_pointer_id
				. '"/>
			</p><ul>'
				. $elements_list
				. '</ul>';
		}

		return $elements_list;
	}


	/**
	 * Return array of:
	 * 1. Content templates, Layouts or Theme templates assigned as custom errors to single posts
	 * 2. Views WP archives, Layouts, PHP theme files assigned as custom errors to WordPress Archives
	 *
	 * @param string $post_type
	 * @param null $access_settings
	 * @param null $gui_common_class
	 *
	 * @return array|array[]
	 *
	 * $access_settings and $gui_common dependency injection used to simplify testing
	 */
	public function get_custom_errors_assigned_elements_array(
		$post_type = 'post',
		$access_settings = null,
		$gui_common_class = null
	) {

		$cache_key = 'toolset_access_custom_errors_items_list' . $post_type;
		$custom_errors_elements_cache = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( $custom_errors_elements_cache ) {
			return $custom_errors_elements_cache;
		}

			$access_settings = $access_settings ? : Settings::get_instance();
		$single_page_errors = array();
		$archive_page_errors = array();

		$post_types_settings = $access_settings->get_types_settings( true, true );

		$single_page_sources = array(
			'error_ct' => __( 'Content Template', 'wpcf-access' ),
			'error_php' => __( 'PHP Template', 'wpcf-access' ),
			'error_layouts' => __( 'Template Layout', 'wpcf-access' ),
		);
		$custom_errors_types = toolset_getnest( $post_types_settings, array(
			'_custom_read_errors',
			$post_type,
			'permissions',
			'read',
		), array() );

		$exclude_roles_array = toolset_getnest( $post_types_settings, array(
			$post_type,
			'permissions',
			'read',
			'roles',
		), array() );

		foreach ( $custom_errors_types as $role => $error_type ) {
			if ( isset( $single_page_sources[ $error_type ] ) ) {

				if ( in_array( $role, $exclude_roles_array, true ) ) {
					continue;
				}
				$error_value = toolset_getnest( $post_types_settings, array(
					'_custom_read_errors_value',
					$post_type,
					'permissions',
					'read',
					$role,
				), '' );
				if ( empty( $error_value ) ) {
					continue;
				}
				$single_page_errors = $this->get_assigned_error_info( $single_page_errors, $error_type, $error_value, $role, $single_page_sources, $gui_common_class, false );
			}
		}

		if ( $access_settings->is_wpml_installed() ) {
			$single_page_errors = $this->get_wpml_group_custom_errors( $single_page_errors, $single_page_sources, $gui_common_class, $post_types_settings, $post_type );
		}

		$archive_page_sources = array(
			'error_ct' => __( 'Wordpress Archive', 'wpcf-access' ),
			'error_php' => __( 'PHP Template', 'wpcf-access' ),
			'error_layouts' => __( 'Template Layout', 'wpcf-access' ),
		);
		$archive_custom_errors_types = toolset_getnest( $post_types_settings, array(
			'_archive_custom_read_errors',
			$post_type,
			'permissions',
			'read',
		), array() );
		foreach ( $archive_custom_errors_types as $role => $error_type ) {
			if ( isset( $archive_page_sources[ $error_type ] ) ) {
				if ( in_array( $role, $exclude_roles_array, true ) ) {
					continue;
				}
				$error_value = toolset_getnest( $post_types_settings, array(
					'_archive_custom_read_errors_value',
					$post_type,
					'permissions',
					'read',
					$role,
				), '' );
				if ( empty( $error_value ) ) {
					continue;
				}
				$archive_page_errors = $this->get_assigned_error_info( $archive_page_errors, $error_type, $error_value, $role, $archive_page_sources, $gui_common_class, true );
			}
		}

		$custom_errors_list = array(
			'single_page_list' => $single_page_errors,
			'archive_page_list' => $archive_page_errors,
		);

		wp_cache_set( $cache_key, $custom_errors_list, self::CACHE_GROUP );

		return $custom_errors_list;
	}


	/**
	 * Generates an array of Toolset elements used in Access custom errors.
	 *
	 * @param array $errors_list
	 * @param string $error_type
	 * @param string $error_value
	 * @param string $role
	 * @param array $sources
	 * @param null $gui_common_class
	 * @param bool $is_archive
	 * @param string $wpml_group
	 *
	 * @return array
	 */
	public function get_assigned_error_info( $errors_list, $error_type, $error_value, $role, $sources, $gui_common_class = null, $is_archive = false, $wpml_group = '' ) {
		$gui_common_class = $gui_common_class ? : GuiCommon::get_instance();
		$role = ucwords( $role );
		$error_slug = sanitize_title( $sources[ $error_type ] . ' ' . $error_value );
		if ( ! isset( $errors_list[ $error_slug ] ) ) {
			$error = array(
				'error_type_slug' => $error_type,
				'error_type_title' => $sources[ $error_type ],
				'error_value' => $error_value,
				'roles' => array( $role ),
				'wpml_group' => $wpml_group,
			);

			if ( 'error_ct' === $error_type ) {
				$error['error_value_converted'] = $gui_common_class->get_view_name( $error_value );
				if ( ! $this->is_views_active || empty( $error['error_value_converted'] ) ) {
					return $errors_list;
				}
			}

			if ( 'error_layouts' === $error_type ) {
				$error['error_value_converted'] = $gui_common_class->get_layout_name( $error_value );
				if ( ! $this->is_layouts_active || empty( $error['error_value_converted'] ) ) {
					return $errors_list;
				}
			}
			$error['edit_link'] = $this->get_edit_link( $error_type, $error_value, $is_archive );
			$errors_list[ $error_slug ] = $error;

		} else {
			if ( ! in_array( $role, $errors_list[ $error_slug ]['roles'] ) ) {
				$errors_list[ $error_slug ]['roles'][] = $role;
			}
		}

		return $errors_list;
	}


	/**
	 * Generates edit link for custom errors element.
	 *
	 * @param string $error_type
	 * @param string $error_value
	 * @param bool $is_archive
	 *
	 * @return string
	 */
	private function get_edit_link( $error_type, $error_value, $is_archive ) {
		$link = '#no-link';
		if ( 'error_php' === $error_type ) {
			$link = get_admin_url() . 'theme-editor.php?file=' . basename( $error_value );
		}
		if ( 'error_ct' === $error_type ) {
			if ( ! $is_archive ) {
				$link = get_admin_url() . 'admin.php?page=ct-editor&ct_id=' . $error_value;
			} else {
				$link = apply_filters( 'wpv_filter_wpa_edit_link', get_admin_url()
					. 'admin.php?page=view-archives-editor&view_id='
					. $error_value, $error_value );
			}
		}
		if ( 'error_layouts' === $error_type ) {
			$link = get_admin_url() . 'admin.php?page=dd_layouts_edit&layout_id=' . $error_value . '&action=edit';
		}

		return $link;
	}


	/**
	 * Generates an array of Toolset elements used in Access custom errors in WPML Groups.
	 *
	 * @param array $single_page_errors
	 * @param array $single_page_sources
	 * @param object $gui_common_class
	 * @param array $post_types_settings
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function get_wpml_group_custom_errors( $single_page_errors, $single_page_sources, $gui_common_class, $post_types_settings, $post_type ) {
		foreach ( $post_types_settings as $group_slug => $group_data ) {
			if ( strpos( $group_slug, 'pcf-wpml-group-' ) > 0 ) {
				if ( $group_data['post_type'] === $post_type ) {
					$custom_errors_types = toolset_getnest( $post_types_settings, array(
						'_custom_read_errors',
						$group_slug,
						'permissions',
						'read',
					), array() );
					$exclude_roles_array = toolset_getnest( $post_types_settings, array(
						$group_slug,
						'permissions',
						'read',
						'roles',
					), array() );
					foreach ( $custom_errors_types as $role => $error_type ) {
						if ( isset( $single_page_sources[ $error_type ] ) ) {
							if ( in_array( $role, $exclude_roles_array, true ) ) {
								continue;
							}
							$error_value = toolset_getnest( $post_types_settings, array(
								'_custom_read_errors_value',
								$group_slug,
								'permissions',
								'read',
								$role,
							), '' );
							if ( empty( $error_value ) ) {
								continue;
							}
							$error_slug = sanitize_title( $single_page_sources[ $error_type ] . ' ' . $error_value );
							if ( isset ( $single_page_errors[ $error_slug ]['roles'] )
								&& in_array( ucwords( $role ), $single_page_errors[ $error_slug ]['roles'], true ) ) {
								continue;
							}
							$role = ucwords( $role );
							if ( 'Everyone' !== $role ) {
								$role .= ' (on ' . $group_data['title'] . ')';
							}
							$single_page_errors = $this->get_assigned_error_info( $single_page_errors, $error_type, $error_value, $role, $single_page_sources, $gui_common_class, false, $group_data['title'] );
						}
					}
				}
			}
		}

		return $single_page_errors;
	}


	/**
	 * Generates tooltip html
	 *
	 * @param string $pointer_id
	 * @param string $pointer_text
	 *
	 * @return string
	 */
	private function generate_pointer_html( $pointer_id, $pointer_text ) {
		return '<div id="' . $pointer_id . '" class="toolset-dashboard-alternative-hidden-div">
					<div class="types-pointer-inner">
						<div class="types-message-content types-table-cell">
							 <p class="types-information-paragraph">
							' . $pointer_text . '
							</p>
						</div>
					</div>
				</div>';
	}

}
