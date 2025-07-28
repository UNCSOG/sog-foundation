<?php

namespace OTGS\Toolset\Types\Controller\Interop\Managed\WPML;

use OTGS\Toolset\Types\Controller\Interop\HandlerInterface2;

class Taxonomies implements HandlerInterface2 {

	const PACKAGE_KIND             = 'Toolset Types Taxonomy Labels';
	const PACKAGE_KIND_SLUG        = 'toolset-types-taxonomy-labels';
	const PACKAGE_CONTEXT_TEMPLATE = 'toolset-types-taxonomy-labels-for-%s';
	const PACKAGE_NAME_TEMPLATE    = 'for-%s';

	const DEFAULT_PACKAGE_CONTEXT = 'toolset-types-taxonomy-labels-default-labels';
	const DEFAULT_PACKAGE_NAME    = 'default-labels';
	const DEFAULT_PACKAGE_TITLE   = 'Default Labels';

	const ADMIN_URL = 'admin.php?page=wpcf-edit-tax&wpcf-tax=%s';

	const TOP_LEVEL_GROUP      = 'Toolset Types Taxonomy';
	const TOP_LEVEL_GROUP_SLUG = 'toolset-types-taxonomy';

	const LABELS_GROUP      = 'Labels';
	const LABELS_GROUP_SLUG = 'toolset-types-taxonomy-labels';

	// Version tracking for default labels
	const DEFAULT_LABELS_VERSION = '1.0';
	const SETTINGS_KEY_DEFAULT_LABELS = 'wpml_default_tax_labels_registered';

	public function initialize() {
		add_action( 'wpcf_init_default_taxonomies_labels', [ $this, 'maybeRegisterDefaultLabels' ] );
		add_action( 'wpcf_register_translations_for_taxonomy', [ $this, 'register' ], 10, 2 );
		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'addGroupsAndLabels' ], 10, 2 );
		add_filter( 'types_taxonomy', [ $this, 'translate' ], 10, 2 );
		add_action( 'wpcf_taxonomy_renamed', [ $this, 'rename' ], 10, 2 );
		add_action( 'wpcf_taxonomy_delete', [ $this, 'delete' ] );
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
	 * @param string $taxonomy
	 *
	 * @return string
	 */
	public static function getPackageContext( $taxonomy ) {
		return sprintf( self::PACKAGE_CONTEXT_TEMPLATE, $taxonomy );
	}

	/**
	 * @param string $taxonomy
	 *
	 * @return string
	 */
	public static function getPackageName( $taxonomy ) {
		return sprintf( self::PACKAGE_NAME_TEMPLATE, $taxonomy );
	}

	/**
	 * @param string $taxonomy
	 *
	 * @return string
	 */
	public static function getAdminUrl( $taxonomy ) {
		return sprintf( self::ADMIN_URL, $taxonomy );
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

	public function registerDefaultLabels() {
		$data = wpcf_custom_taxonomies_default();
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
	 * @param string $taxonomy
	 */
	public function register( $data, $taxonomy ) {
		if ( isset( $data['description'] ) ) {
			do_action(
				'wpml_register_string',
				$data['description'],
				$taxonomy . '-description',
				$this->getPackage( $taxonomy, toolset_getarr( $data, 'labels', [] ) ),
				$taxonomy . '-description',
				'LINE'
			);
		}

		$default = wpcf_custom_taxonomies_default();

		foreach ( $data['labels'] as $label => $string ) {
			if ( empty( $string ) ) {
				continue;
			}

			// Check others for defaults
			if ( $string !== toolset_getnest( $default, [ 'labels', $label ], '' ) ) {
				do_action(
					'wpml_register_string',
					$string,
					$taxonomy . '-' . $label,
					$this->getPackage( $taxonomy, toolset_getarr( $data, 'labels', [] ) ),
					$taxonomy . '-' . $label,
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
	 * @param array  $data
	 * @param string $taxonomy
	 *
	 * @return array
	 */
	public function translate( $data, $taxonomy ) {
		if ( ! empty( $data['description'] ) ) {
			$data['description'] = apply_filters(
        'wpml_translate_single_string',
        $data['description'],
        self::getPackageContext( $taxonomy ),
				$taxonomy . '-description'
    	);
		}

		if ( false === toolset_getnest( $data, ['labels', 'name'], false ) ) {
			$data['labels']['name'] = $taxonomy;
		}

		if ( false === toolset_getnest( $data, ['labels', 'singular_name'], false ) ) {
			$data['labels']['singular_name'] = $data['labels']['name'];
		}

		$default = wpcf_custom_taxonomies_default();

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
				self::getPackageContext( $taxonomy ),
				$taxonomy . '-' . $label
			);

			/**
			 * Translate (and register with WPML) the taxonomy strings in the correct gettext context
			 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/types-1323
			 */
			if ( 'name' === $label && $string === toolset_getnest( $data, [ 'labels', $label ] ) ) {
				$data['labels'][ $label ] = _x( $string, 'taxonomy general name' );
			}
			if ( 'singular_name' === $label && $string === toolset_getnest( $data, [ 'labels', $label ] ) ) {
				$data['labels'][ $label ] = _x( $string, 'taxonomy singular name' );
			}
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
