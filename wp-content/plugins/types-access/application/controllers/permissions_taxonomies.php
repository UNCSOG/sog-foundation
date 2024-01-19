<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Models\UserRoles;

/**
 * Main taxonomies controller
 *
 * Class PermissionsTaxonomies
 *
 * @package OTGS\Toolset\Access\Controllers
 * @since 2.7
 */
class PermissionsTaxonomies {

	private static $instance;


	/**
	 * @return PermissionsTaxonomies
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
	 * PermissionsPostTypes constructor.
	 */
	public function __construct() {
		add_action( 'registered_taxonomy', array( $this, 'registered_registered_taxonomy' ), 10, 3 );
	}


	/**
	 * Maps rules and settings for taxonomies registered outside of Types.
	 *
	 * @param string $taxonomy
	 * @param mixed $object_type
	 * @param array $args
	 */
	public function registered_registered_taxonomy( $taxonomy, $object_type, $args ) {
		global $wp_taxonomies, $wpcf_access;

		$access_settings = Settings::get_instance();
		$access_capabilties = Capabilities::get_instance();

		$settings_access = $access_settings->get_tax_settings();

		// do basic access tests
		if ( isset( $wp_taxonomies[ $taxonomy ] ) ) {
			$caps = $access_capabilties->get_tax_caps();

			// Map pre-defined capabilities
			$new_caps = array();
			$valid = true;
			foreach ( $caps as $cap_slug => $cap_data ) {
				// Create cap slug
				$new_cap_slug = str_replace( '_terms', '_'
					. sanitize_title( $wp_taxonomies[ $taxonomy ]->name ), $cap_slug );

				if ( ! empty( $args['_builtin'] )
					|| ( isset( $wp_taxonomies[ $taxonomy ]->cap->$cap_slug )
						&& $wp_taxonomies[ $taxonomy ]->cap->$cap_slug == $cap_data['default'] ) ) {
					$new_caps[ $cap_slug ] = $new_cap_slug;
				} elseif ( isset( $wp_taxonomies[ $taxonomy ]->cap->$cap_slug )
					&& isset( $wpcf_access->rules->taxonomies[ $wp_taxonomies[ $taxonomy ]->cap->$cap_slug ] ) ) {
					$new_caps[ $cap_slug ] = $wp_taxonomies[ $taxonomy ]->cap->$cap_slug;
				}
			}
			// provide access pointers
			$wp_taxonomies[ $taxonomy ]->__accessIsCapValid = ! $access_capabilties->check_cap_conflict( array_values( $new_caps ) );
			$wp_taxonomies[ $taxonomy ]->__accessIsNameValid = isset( $wp_taxonomies[ $taxonomy ]->labels );
			$wp_taxonomies[ $taxonomy ]->__accessNewCaps = $new_caps;

			if ( isset( $settings_access[ $taxonomy ] ) ) {
				$data = $settings_access[ $taxonomy ];
				$mode = isset( $data['mode'] ) ? $data['mode'] : 'not_managed';
				$data['mode'] = $mode;

				if (
					$mode == 'not_managed'
					||
					! $wp_taxonomies[ $taxonomy ]->__accessIsCapValid
					||
					! $wp_taxonomies[ $taxonomy ]->__accessIsNameValid
				) {
					if ( ! isset( $settings_access[ $taxonomy ]['mode'] ) ) {
						$settings_access[ $taxonomy ]['mode'] = 'not_managed';
						$access_settings->updateAccessTaxonomies( $settings_access );
					}

					return;
				}

				foreach ( $new_caps as $cap_slug => $new_cap_slug ) {
					// Alter if tax is built-in or other has default capability settings
					if ( ! empty( $args['_builtin'] )
						|| ( isset( $wp_taxonomies[ $taxonomy ]->cap->$cap_slug )
							&& $wp_taxonomies[ $taxonomy ]->cap->$cap_slug == $caps[ $cap_slug ]['default'] ) ) {
						$wp_taxonomies[ $taxonomy ]->cap->$cap_slug = $new_cap_slug;
						$wpcf_access->rules->taxonomies[ $new_cap_slug ]['follow'] = $mode == 'follow';
						if ( isset( $data['permissions'][ $cap_slug ] ) ) {
							$wpcf_access->rules->taxonomies[ $new_cap_slug ]['roles'] = $data['permissions'][ $cap_slug ]['roles'];
							$wpcf_access->rules->taxonomies[ $new_cap_slug ]['users'] = isset( $data['permissions'][ $cap_slug ]['users'] )
								? $data['permissions'][ $cap_slug ]['users'] : array();
						}

						// Otherwise just map capabilities
					} elseif ( isset( $wp_taxonomies[ $taxonomy ]->cap->$cap_slug )
						&& isset( $wpcf_access->rules->taxonomies[ $wp_taxonomies[ $taxonomy ]->cap->$cap_slug ] ) ) {
						$wpcf_access->rules->taxonomies[ $wp_taxonomies[ $taxonomy ]->cap->$cap_slug ]['follow'] = $mode
							== 'follow';
						if ( isset( $data['permissions'][ $cap_slug ] ) ) {
							$wpcf_access->rules->taxonomies[ $wp_taxonomies[ $taxonomy ]->cap->$cap_slug ]['roles'] = $data['permissions'][ $cap_slug ]['roles'];
							$wpcf_access->rules->taxonomies[ $wp_taxonomies[ $taxonomy ]->cap->$cap_slug ]['users'] = isset( $data['permissions'][ $cap_slug ]['users'] )
								? $data['permissions'][ $cap_slug ]['users'] : array();
						}
					}
					$wpcf_access->rules->taxonomies[ $wp_taxonomies[ $taxonomy ]->cap->$cap_slug ]['taxonomy'] = $taxonomy;
				}
			}
		}
	}


	/**
	 * @param array $allcaps
	 * @param array $caps
	 * @param array $args
	 * @param mixed $user
	 *
	 * @return array
	 */
	public function get_taxonomy_caps( $allcaps, $caps, $args, $user ) {
		$settings = Settings::get_instance();
		$access_roles = UserRoles::get_instance();
		$access_capabilities = Capabilities::get_instance();

		$cap = $caps[0];
		$taxonomy = str_replace( array( 'manage_', 'edit_', 'assign_', 'delete_' ), '', $cap );
		list( $plural, $singular ) = $this->get_taxonomy_names( $taxonomy );
		if ( empty( $singular ) || empty( $plural ) ) {
			return $allcaps;
		}
		$roles = $access_roles->get_current_user_roles();
		$tax_settings = $settings->get_tax_settings();
		if ( ! $this->is_taxonomy_managed( $singular, $tax_settings ) ) {
			return $allcaps;
		}

		$taxonomy_settings = $tax_settings[ $singular ]['permissions'];
		if ( 'category' !== $singular && isset( $tax_settings[ $singular ]['mode'] )
			&& 'follow'
			=== $tax_settings[ $singular ]['mode'] ) {
			if ( ! $this->is_taxonomy_managed( 'category', $tax_settings ) ) {
				return $allcaps;
			}
			$taxonomy_settings = $tax_settings['category']['permissions'];
		}

		$requested_capabilties = array(
			'assign_terms' => true,
			'delete_terms' => true,
			'edit_terms' => true,
			'manage_terms' => true,
		);
		$user_caps = array( 'manage' => false, 'edit' => false, 'delete' => false, 'asign' => false );

		$parsed_caps = $this->parse_taxonomy_caps( $taxonomy_settings, $requested_capabilties, $roles );
		$user_caps = $this->generate_user_caps( $parsed_caps, $user_caps );
		$allcaps = $access_capabilities->bulk_allcaps_update( $user_caps, $singular, $user, $allcaps, $plural );

		return $allcaps;
	}


	private function is_taxonomy_managed( $singular, $tax_settings ) {
		if (
			! isset( $tax_settings[ $singular ] )
			|| (
				isset( $tax_settings[ $singular ]['mode'] ) && 'not_managed' == $tax_settings[ $singular ]['mode']
			)
		) {
			return false;
		}

		return true;
	}


	/**
	 * @param $parsed_caps
	 * @param $user_caps
	 *
	 * @return mixed
	 */
	private function generate_user_caps( $parsed_caps, $user_caps ) {

		foreach ( $parsed_caps as $cap_slug => $cap_status ) {
			$cap_name = str_replace( '_terms', '', $cap_slug );
			if ( $cap_status ) {
				$user_caps[ $cap_name ] = true;
			}
		}

		return $user_caps;
	}


	/**
	 *
	 * @param $tax_settings
	 * @param $requested_capabilities
	 * @param $roles
	 *
	 * @return mixed
	 */
	private function parse_taxonomy_caps( $tax_settings, $requested_capabilities, $roles ) {
		global $current_user;
		$user_id = $current_user->ID;
		$output = $requested_capabilities;
		foreach ( $requested_capabilities as $cap => $status ) {
			if ( ! isset( $tax_settings[ $cap ] ) ) {
				continue;
			}

			${$cap} = $tax_settings[ $cap ]['roles'];

			if ( isset( $tax_settings[ $cap ]['users'] ) ) {
				${$cap . '_users'} = $tax_settings[ $cap ]['users'];
			}

			$output[ $cap ] = false;

			if ( isset( ${$cap . '_users'} ) && in_array( $user_id, ${$cap . '_users'} ) ) {
				$output[ $cap ] = true;
				continue;
			}
			$roles_check = array_intersect( $roles, ${$cap} );
			if ( ! empty( $roles_check ) ) {
				$output[ $cap ] = true;
				continue;
			}

		}

		return $output;
	}


	/**
	 * @param $tax_name
	 *
	 * @return array
	 */
	private function get_taxonomy_names( $tax_name ) {
		global $wpcf_access;

		if ( 'categories' === $tax_name ) {
			return array( 'categories', 'category' );
		}
		$settings = Settings::get_instance();
		$taxonomies = $settings->get_taxonomies();
		$post_type_object = null;
		$tax_plural = $tax_singular = '';

		if ( ! isset( $wpcf_access->taxonomy_info[ $tax_name ] ) ) {
			if ( isset( $taxonomies[ $tax_name ] ) ) {
				$tax_object = get_taxonomy( $tax_name );
				$tax_singular = $tax_name;
				$tax_plural = sanitize_title_with_dashes( strtolower( $tax_object->label ) );
			} else {
				$tax_plural = $tax_name;
				foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {
					if ( $tax_plural == strtolower( $taxonomy_data->label ) ) {
						$tax_singular = $taxonomy_slug;
					}
				}
			}
			$wpcf_access->taxonomy_info[ $tax_name ] = array( $tax_plural, $tax_singular );
		} else {
			$tax_singular = $wpcf_access->taxonomy_info[ $tax_name ][1];
			$tax_plural = $wpcf_access->taxonomy_info[ $tax_name ][0];
		}

		return array( $tax_plural, $tax_singular );
	}
}
