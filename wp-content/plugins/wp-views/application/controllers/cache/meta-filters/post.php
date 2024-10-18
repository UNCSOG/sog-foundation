<?php

namespace OTGS\Toolset\Views\Controller\Cache\MetaFilters;

use OTGS\Toolset\Views\Model\Wordpress\Transient;

/**
 * Postmeta filter fields cache controller.
 *
 * @since 3.0
 */
class Post {

	const TRANSIENT_KEY = 'wpv_transient_meta_filter_groups';

	const TYPES_GROUP_UPDATED_ACTION = 'types_fields_group_post_saved';

	const GET_HOOK_HANDLE = 'wpv_get_meta_filter_groups_cache';

	const DELETE_HOOK_HANDLE = 'wpv_delete_meta_filter_groups_cache';

	/**
	 * @var Transient
	 */
	protected $transient_manager = null;

	/**
	 * Constructor
	 *
	 * @param Transient $transient_manager
	 * @since 2.8.1
	 */
	public function __construct( Transient $transient_manager ) {
		$this->transient_manager = $transient_manager;
	}

	/**
	 * Initialize the postmeta filter fields cache management.
	 *
	 * @since 3.0
	 */
	public function initialize() {
		$this->add_api_hooks();
		$this->add_invalidation_hooks();
	}

	/**
	 * Register API hooks to get, set and delete the cache for postmeta filter fields.
	 *
	 * @since 3.0
	 */
	private function add_api_hooks() {
		add_filter( self::GET_HOOK_HANDLE, array( $this, 'get_cache' ) );
		add_action( self::DELETE_HOOK_HANDLE, array( $this, 'delete_cache' ) );

	}

	/**
	 * Register callbacks to invalidate the cache.
	 *
	 * @since 3.0
	 */
	private function add_invalidation_hooks() {
		add_action( self::TYPES_GROUP_UPDATED_ACTION, array( $this, 'delete_cache' ) );
	}

	/**
	 * Get the cache.
	 *
	 * @param array $cache Dummy value for filter usage
	 * @since 3.0
	 */
	public function get_cache( $cache = array() ) {
		$transient = $this->transient_manager->get_transient( self::TRANSIENT_KEY );

		if ( false !== $transient ) {
			return $transient;
		}

		$transient = $this->generate_cache();

		return $transient;
	}

	/**
	 * Generate the cache.
	 *
	 * @return array
	 * @since 3.0
	 */
	private function generate_cache() {
		$transient = array();

		if (
			function_exists( 'wpcf_admin_fields_get_groups' )
			&& function_exists( 'wpcf_admin_fields_get_fields_by_group' )
		) {
			$groups = wpcf_admin_fields_get_groups( TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, false, false, true );
			if ( ! empty( $groups ) ) {
				foreach ( $groups as $group ) {
					$fields = wpcf_admin_fields_get_fields_by_group( $group['id'], 'slug', true, false, true );
					// @since m2m wpcf_admin_fields_get_fields_by_group returns strings for repeatng fields groups
					$fields = array_filter( $fields, 'is_array' );
					if ( ! empty( $fields ) ) {
						$subgroup_items = array();

						foreach ( $fields as $field ) {
							if ( ! \WPV_Meta_Field_Filter::can_filter_by( $field['meta_key'] ) ) {
								continue;
							}
							$field_data = wpv_types_get_field_data( $field['meta_key'] );
							$subgroup_items[ 'postmeta_' . $field['id'] ] = array(
								'name'			=> $field['name'],
								'present'		=> 'custom-field-' . $field['meta_key'] . '_compare',
								'params'		=> array(
									'attributes'	=> array(
										'field'	=> $field['meta_key'],
										'type' => isset( $field_data['type'] ) ? $field_data['type'] : '',
									)
								)
							);
						}
						$transient[] = array(
							'custom_search_filter_group' => $group['name'],
							'custom_search_filter_items' => $subgroup_items
						);
					}
				}
			}
		}

		$this->transient_manager->set_transient( self::TRANSIENT_KEY, $transient );

		return $transient;
	}

	public function delete_cache() {
		$this->transient_manager->delete_transient( self::TRANSIENT_KEY );
	}
}
