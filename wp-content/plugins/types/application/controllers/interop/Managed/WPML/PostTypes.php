<?php

namespace OTGS\Toolset\Types\Controller\Interop\Managed\WPML;

use OTGS\Toolset\Types\Controller\Interop\HandlerInterface2;

class PostTypes implements HandlerInterface2 {

	const PACKAGE_KIND             = 'Toolset Types CPT Labels';
	const PACKAGE_KIND_SLUG        = 'toolset-types-cpt-labels';
	const PACKAGE_CONTEXT_TEMPLATE = 'toolset-types-cpt-labels-for-%s';
	const PACKAGE_NAME_TEMPLATE    = 'for-%s';

	const DEFAULT_PACKAGE_CONTEXT = 'toolset-types-cpt-labels-default-labels';
	const DEFAULT_PACKAGE_NAME    = 'default-labels';
	const DEFAULT_PACKAGE_TITLE   = 'Default Labels';

	const ADMIN_URL = 'admin.php?page=wpcf-edit-type&wpcf-post-type=%s';

	const TOP_LEVEL_GROUP      = 'Toolset Types CPT';
	const TOP_LEVEL_GROUP_SLUG = 'toolset-types-cpt';

	const LABELS_GROUP      = 'Labels';
	const LABELS_GROUP_SLUG = 'toolset-types-cpt-labels';

	// Version tracking for default labels
	const DEFAULT_LABELS_VERSION = '1.0';
	const SETTINGS_KEY_DEFAULT_LABELS = 'wpml_default_cpt_labels_registered';

	public function initialize() {
		add_action( 'wpcf_init_default_types_labels', [ $this, 'maybeRegisterDefaultLabels' ] );
		add_action( 'wpcf_register_translations_for_cpt', [ $this, 'register' ], 10, 2 );
		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'addGroupsAndLabels' ], 10, 2 );
		add_filter( 'wpml_tm_adjust_translation_job', [ $this, 'reorderFields' ], 10, 2 );
		add_filter( 'types_post_type', [ $this, 'translate' ], 10, 2 );
		add_action( 'wpcf_post_type_renamed', [ $this, 'rename' ], 10, 2 );
		add_action( 'wpcf_post_type_delete', [ $this, 'delete' ] );
	}

	/**
	 * Check if default labels need to be registered and register them if necessary
	 */
	public function maybeRegisterDefaultLabels() {
		// Check if we've already registered default labels for this version
		$settings = wpcf_get_settings();
		$registered_version = isset( $settings[ self::SETTINGS_KEY_DEFAULT_LABELS ] ) 
			? $settings[ self::SETTINGS_KEY_DEFAULT_LABELS ] 
			: false;

		// Skip if already registered for current version
		if ( $registered_version === self::DEFAULT_LABELS_VERSION ) {
			return;
		}

		// Register default labels
		$this->registerDefaultLabels();

		// Update settings to mark as registered
		$settings[ self::SETTINGS_KEY_DEFAULT_LABELS ] = self::DEFAULT_LABELS_VERSION;
		wpcf_save_settings( $settings );
	}

	/**
	 * @param string $postType
	 *
	 * @return string
	 */
	public static function getPackageContext( $postType ) {
		return sprintf( self::PACKAGE_CONTEXT_TEMPLATE, $postType );
	}

	/**
	 * @param string $postType
	 *
	 * @return string
	 */
	public static function getPackageName( $postType ) {
		return sprintf( self::PACKAGE_NAME_TEMPLATE, $postType );
	}

	/**
	 * @param string $postType
	 *
	 * @return string
	 */
	public static function getAdminUrl( $postType ) {
		return sprintf( self::ADMIN_URL, $postType );
	}

	/**
	 * @param string   $type
	 * @param string[] $labels
	 *
	 * @return array
	 */
	private function getPackage( $type, $labels ) {
		return [
			'kind'      => self::PACKAGE_KIND,
			'kind_slug' => self::PACKAGE_KIND_SLUG,
			'name'      => self::getPackageName( $type ),
			'title'     => toolset_getarr( $labels, 'name', $type ),
			'edit_link' => self::getAdminUrl( $type ),
		];
	}

    /**
     * Register default labels with WPML
     * This method should only be called when necessary
     */
	public function registerDefaultLabels() {
		$data = wpcf_custom_types_default();
		$labels = toolset_getarr( $data, 'labels', [] );
		foreach ( $labels as $label => $string ) {
			if ( empty( $string ) ) {
				continue;
			}

			// Check others for defaults
			do_action(
				'wpml_register_string',
				$string,
				$label,
				[
					'kind'      => self::PACKAGE_KIND,
					'kind_slug' => self::PACKAGE_KIND_SLUG,
					'name'      => self::DEFAULT_PACKAGE_NAME,
					'title'     => self::DEFAULT_PACKAGE_TITLE,
					'edit_link' => '',
				],
				$label,
				'LINE'
			);
		}
	}

	/**
	 * @param array  $data
	 * @param string $postType
	 */
	public function register( $data, $postType ) {
		if ( isset( $data['description'] ) ) {
			do_action(
				'wpml_register_string',
				$data['description'],
				$postType . '-description',
				$this->getPackage( $postType, toolset_getarr( $data, 'labels', [] ) ),
				$postType . '-description',
				'LINE'
			);
		}

		$default = wpcf_custom_types_default();

		foreach ( $data['labels'] as $label => $string ) {
			if ( empty( $string ) ) {
				continue;
			}

			// Check others for defaults
			if ( $string !== toolset_getnest( $default, [ 'labels', $label ], '' ) ) {
				do_action(
					'wpml_register_string',
					$string,
					$postType . '-' . $label,
					$this->getPackage( $postType, toolset_getarr( $data, 'labels', [] ) ),
					$postType . '-' . $label,
					'LINE'
				);
			}
		}
	}

	/**
	 * @param \stdClass $job
	 *
	 * @return bool
	 */
	private function isOurJob( $job ) {
		if ( ! property_exists( $job, 'original_post_type' ) ) {
			return false;
		}
		return 'package_' . self::PACKAGE_KIND_SLUG === $job->original_post_type;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function processField( $field ) {
		$fieldTitle = (string) toolset_getarr( $field, 'title' );

		$length = strlen( '-description' );
		if ( substr( $fieldTitle, -$length ) === '-description' ) {
			$field['group'] = [
				self::TOP_LEVEL_GROUP_SLUG => self::TOP_LEVEL_GROUP,
			];
		} else {
			$field['group'] = [
				self::TOP_LEVEL_GROUP_SLUG => self::TOP_LEVEL_GROUP,
				self::LABELS_GROUP_SLUG    => self::LABELS_GROUP,
			];
		}

		$field['title'] = substr( $fieldTitle, strrpos( $fieldTitle, '-' ) + 1 );

		return $field;
	}

	/**
	 * @param array[]   $fields
	 * @param \stdClass $job
	 *
	 * @return array[]
	 */
	public function addGroupsAndLabels( $fields, $job ) {
		if ( ! $this->isOurJob( $job ) ) {
			return $fields;
		}
		foreach ( $fields as &$field ) {
			$field = $this->processField( $field );
		}

		return $fields;
	}

	/**
	 * @param array     $fields
	 * @param \stdClass $job
	 *
	 * @return array
	 */
	public function reorderFields( $fields, $job ) {
		if ( ! $this->isOurJob( $job ) ) {
			return $fields;
		}

		$fieldsBySection = [
			'settings' => [],
			'labels'   => [],
			'orphaned' => [],
		];

		/**
		 * @param array $field
		 *
		 * @return int|null
		 */
		$getGroupId = function( $field ) {
			// See WPML on WPML_TM_Xliff_Writer::get_translation_unit_data.
			$extraData      = toolset_getnest( $field, [ 'attributes', 'extradata' ], '' );
			$fieldExtraData = json_decode( str_replace( '&quot;', '"', $extraData ) );
			if ( null === $fieldExtraData ) {
				return null;
			}

			$fieldExtraDataList = (array) $fieldExtraData;
			return toolset_getarr( $fieldExtraDataList, 'group_id', null );
		};

		array_walk( $fields, function( $field, $fieldKey ) use ( $getGroupId, &$fieldsBySection ) {
			$groupId = $getGroupId( $field );

			if ( null === $groupId ) {
				$fieldsBySection['orphaned'][] = $field;
				return;
			}

			if ( $groupId === self::TOP_LEVEL_GROUP_SLUG ) {
				$fieldsBySection['settings'][] = $field;
				return;
			}

			if ( $groupId === self::TOP_LEVEL_GROUP_SLUG . '/' . self::LABELS_GROUP_SLUG ) {
				$fieldsBySection['labels'][] = $field;
				return;
			}

			$fieldsBySection['orphaned'][] = $field;
		} );

		return array_merge(
			$fieldsBySection[ 'settings' ],
			$fieldsBySection[ 'labels' ],
			$fieldsBySection[ 'orphaned' ]
		);
	}

	/**
	 * @param array  $data
	 * @param string $postType
	 *
	 * @return array
	 */
	public function translate( $data, $postType ) {
		if ( ! empty( $data['description'] ) ) {
			$data['description'] = apply_filters(
        'wpml_translate_single_string',
        $data['description'],
        self::getPackageContext( $postType ),
				$postType . '-description'
    	);
		}

		$default = wpcf_custom_types_default();

		foreach ( $data['labels'] as $label => $string ) {
			if ( empty( $string ) ) {
				continue;
			}

			if ( $string === toolset_getnest( $default, [ 'labels', $label ], '' ) ) {
				$data['labels'][ $label ] = apply_filters(
					'wpml_translate_single_string',
					$string,
					self::DEFAULT_PACKAGE_CONTEXT,
					$label
				);
				continue;
			}

			$data['labels'][ $label ] = apply_filters(
				'wpml_translate_single_string',
				$string,
				self::getPackageContext( $postType ),
				$postType . '-' . $label
			);
		}

		return $data;
	}

	/**
	 * @param string $newSlug
	 * @param string $oldSlug
	 */
	public function rename( $newSlug, $oldSlug ) {
		global $wpdb;

		$update_data  = array(
			'name'      => self::getPackageName( $newSlug ),
			'edit_link' => self::getAdminUrl( $newSlug ),
		);
		$update_where = array(
			'kind_slug' => self::PACKAGE_KIND_SLUG,
			'name'      => self::getPackageName( $oldSlug ),
		);

		$wpdb->update(
			$wpdb->prefix . 'icl_string_packages',
			$update_data,
			$update_where
		);

		$wpdb->query(
			$wpdb->prepare(
				"
				UPDATE {$wpdb->prefix}icl_strings
				SET context = %s,
					name = REPLACE( name, %s, %s )
				WHERE context = %s
				",
				[ 
					self::getPackageContext( $newSlug ),
					$oldSlug . '-',
					$newSlug . '-',
					self::getPackageContext( $oldSlug ),
				]
			)
		);

		do_action( 'wpml_st_refresh_domain', self::getPackageContext( $newSlug ) );
	}

	/**
	 * @param string $slug
	 */
	public function delete( $slug ) {
		do_action(
			'wpml_delete_package',
			self::getPackageName( $slug ),
			self::PACKAGE_KIND
		);
	}

}
