<?php

namespace OTGS\Toolset\Access\Viewmodels;

use OTGS\Toolset\Access\Controllers\AccessOutputTemplateRepository;
use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Models\UserRoles;

/**
 * Generate Access permission tables
 *
 * Class PermissionsTablesTaxonomies
 *
 * @package OTGS\Toolset\Access\Viewmodels
 * @since 2.8.4
 */
class PermissionsTablesTaxonomies {

	/**
	 * @var object
	 */
	private static $instance;


	/**
	 * @return object|PermissionsGui
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
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


	public function get_permission_table_for_taxonomies() {

		$tab_slug = 'taxonomy';
		$access_settings = Settings::get_instance();
		$access_roles = UserRoles::get_instance();
		$permissionGui = PermissionsGui::get_instance();

		$post_types_settings = $access_settings->get_types_settings( true, true );
		$post_types_available = $access_settings->get_post_types();
		$post_types_available = $access_settings->object_to_array( $post_types_available );

		$taxonomies_settings = $access_settings->get_tax_settings( true, true );
		$taxonomies_available = $access_settings->get_taxonomies();
		$taxonomies_available = $access_settings->object_to_array( $taxonomies_available );

		$roles = $access_roles->get_editable_roles();
		$section_statuses = $permissionGui->get_section_statuses();

		$container_class = 'is-enabled';
		$enabled = true;

		list( $access_notices, $post_types_available, $access_bypass_template, $access_conflict_template ) = $permissionGui->filter_post_types( $post_types_available, $post_types_settings );

		$post_types_available = $permissionGui->order_post_types( $post_types_available );
		$output = '';
		foreach ( $taxonomies_available as $tax_slug => $tax_data ) {
			if (
				isset( $tax_data['__accessIsNameValid'] )
				&& ! $tax_data['__accessIsNameValid']
			) {
				$access_notices .= sprintf( $access_bypass_template, __( 'Taxonomy', 'wpcf-access' ), $tax_data['labels']['singular_name'] );
				unset( $taxonomies_available[ $tax_slug ] );
				continue;
			}
			if (
				isset( $tax_data['__accessIsCapValid'] )
				&& ! $tax_data['__accessIsCapValid']
			) {
				$access_notices .= sprintf( $access_conflict_template, __( 'Taxonomy', 'wpcf-access' ), $tax_data['labels']['singular_name'] );
				unset( $taxonomies_available[ $tax_slug ] );
				continue;
			}

			$taxonomies_available[ $tax_slug ]['supports'] = array_flip( $tax_data['object_type'] );
			if ( isset( $taxonomies_settings[ $tax_slug ] ) ) {
				$taxonomies_available[ $tax_slug ]['_wpcf_access_capabilities'] = $taxonomies_settings[ $tax_slug ];
			}

			if ( $enabled ) {
				$mode = isset( $tax_data['_wpcf_access_capabilities']['mode'] )
					? $tax_data['_wpcf_access_capabilities']['mode'] : 'follow';
				if ( empty( $tax_data['supports'] ) ) {
					continue;
				}

				foreach ( $tax_data['supports'] as $supports_type => $true ) {
					if ( ! isset( $post_types_available[ $supports_type ]['_wpcf_access_capabilities']['mode'] ) ) {
						continue;
					}

					$mode = $post_types_available[ $supports_type ]['_wpcf_access_capabilities']['mode'];

					if ( ! isset( $post_types_available[ $supports_type ]['_wpcf_access_capabilities'][ $mode ] ) ) {
						continue;
					}

					$supports_check[ $tax_slug ][ md5( $mode
						. serialize( $post_types_available[ $supports_type ]['_wpcf_access_capabilities'][ $mode ] ) ) ][] = $post_types_available[ $supports_type ]['labels']['name'];
				}
			}
		}

		// Put Categories and Tags in front
		$native_taxonomies = array( 'post_tag', 'category' );
		foreach ( $native_taxonomies as $native_taxonomy ) {
			if ( isset( $taxonomies_available[ $native_taxonomy ] ) ) {
				$clone = array( $native_taxonomy => $taxonomies_available[ $native_taxonomy ] );
				unset( $taxonomies_available[ $native_taxonomy ] );
				$taxonomies_available = $clone + $taxonomies_available;
			}
		}

		$capabilities = Capabilities::get_instance();
		$custom_data = $capabilities->get_tax_caps();


		foreach ( $taxonomies_available as $tax_slug => $tax_data ) {
			$mode = 'not_managed';
			if ( $tax_data['public'] === 'hidden' ) {
				continue;
			}
			// Set data
			if ( isset( $tax_data['_wpcf_access_capabilities']['mode'] ) ) {
				$mode = $tax_data['_wpcf_access_capabilities']['mode'];
			} elseif ( $enabled ) {
				$mode = $access_settings->get_taxonomy_mode( $tax_slug, $mode );
			} else {
				$mode = 'not_managed';
			}

			// For built-in set default to 'not_managed'
			if ( in_array( $tax_slug, $native_taxonomies ) ) {
				$mode = isset( $tax_data['_wpcf_access_capabilities']['mode'] )
					? $tax_data['_wpcf_access_capabilities']['mode'] : 'not_managed';
			}

			if ( isset( $tax_data['_wpcf_access_capabilities']['permissions'] ) ) {
				foreach ( $tax_data['_wpcf_access_capabilities']['permissions'] as $cap_slug => $cap_data ) {
					$custom_data[ $cap_slug ]['roles'] = $cap_data['roles'];
					$custom_data[ $cap_slug ]['users'] = isset( $cap_data['users'] ) ? $cap_data['users'] : array();
				}
			}

			$is_managed = ( $mode != 'not_managed' );
			$container_class = 'is-enabled';
			if ( ! $is_managed ) {
				$container_class = 'otg-access-settings-section-item-not-managed';
			}
			$is_section_opened = false;
			if ( isset( $section_statuses[ $tax_slug ] ) && $section_statuses[ $tax_slug ] == 1 ) {
				$is_section_opened = true;
			}

			$output .= '<div class="otg-access-settings-section-item js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item '
				. $container_class
				. '">';
			$output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="'
				. esc_attr( $tax_slug )
				. '">'
				. $tax_data['labels']['name']
				. '<span class="otg-access-settings-section-item-managed js-otg-access-settings-section-item-managed" style="display:'
				. ( ! $is_section_opened ? 'block' : 'none' )
				. '">'
				. ( $is_managed ? __( '(Managed by Access)', 'wpcf-access' )
					: __( '(Not managed by Access)', 'wpcf-access' ) )
				. '</span>'
				. '</h4>';

			$output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-'
				. esc_attr( $tax_slug )
				. '" style="display:'
				. ( ! $is_section_opened ? 'none' : 'block' )
				. '">';

			// Add warning if shared and settings are different
			$disable_same_as_parent = false;
			if (
				$enabled
				&& isset( $supports_check[ $tax_slug ] )
				&& count( $supports_check[ $tax_slug ] ) > 1
			) {
				$txt = array();
				foreach ( $supports_check[ $tax_slug ] as $sc_tax_md5 => $sc_tax_md5_data ) {
					$txt = array_merge( $txt, $sc_tax_md5_data );
				}
				$last_element = array_pop( $txt );
				$warning = '<br /><img src="'
					. TACCESS_ASSETS_URL
					. '/images/warning.png" style="position:relative;top:2px;" />'
					. sprintf( __( 'You need to manually set the access rules for taxonomy %s. That taxonomy is shared between several post types that have different access rules.' ),
						$tax_data['labels']['name'],
						implode( ', ', $txt ), $last_element );
				$output .= $warning;
				$disable_same_as_parent = true;
			}

			// Managed checkbox - Custom taxonomies section
			$output .= '<p>';
			$output .= '<label><input type="checkbox" class="not-managed js-wpcf-enable-access" name="types_access[tax]['
				. $tax_slug
				. '][not_managed]" value="1"';
			if ( ! $enabled ) {
				$output .= ' disabled="disabled" readonly="readonly"';
			}
			$output .= $is_managed ? ' checked="checked"' : '';
			$output .= '/>' . __( 'Managed by Access', 'wpcf-access' ) . '</label>';
			$output .= '</p>';

			if ( $tax_slug != 'category' ) {
				// 'Same as parent' checkbox
				$output .= '<p>';
				$output .= '<label><input type="checkbox" class="follow js-wpcf-follow-parent" name="types_access[tax]['
					. $tax_slug
					. '][mode]" value="follow"';
				if ( ! $enabled ) {
					$output .= ' disabled="disabled" readonly="readonly" checked="checked"';
				} elseif ( $disable_same_as_parent ) {
					$output .= ' disabled="disabled" readonly="readonly"';
				} else {
					$output .= $mode == 'follow' ? ' checked="checked"' : '';
				}
				$output .= ' />' . __( 'Same as Category', 'wpcf-access' ) . '</label>';
				$output .= '</p>';
			}

			$output .= '<div class="wpcf-access-mode-custom">';
			$output .= $permissionGui->permissions_table(
				$roles,
				$custom_data,
				$custom_data,
				'tax',
				$tax_slug,
				$enabled,
				$is_managed,
				$taxonomies_settings,
				array(),
				'tax'
			);
			$output .= '</div>	<!-- .wpcf-access-mode-custom -->';

			$output .= '<p class="wpcf-access-buttons-wrap">';
			$output .= $permissionGui->generate_submit_button( $enabled, $is_managed, $tax_data['labels']['name'] );
			$output .= '</p>';

			$output .= '</div>	<!-- wpcf-access-mode -->';
			$output .= '</div>	<!-- wpcf-access-type-item -->';
		}

		$section_content = $output;

		$template_repository = AccessOutputTemplateRepository::get_instance();
		$output = $template_repository->render( $template_repository::PERMISSION_TABLE_HEADERS_TEMPLATE,
			array(
				'tab_slug' => $tab_slug,
				'section_content' => $section_content,
			)
		);

		return $output;
	}
}
