<?php

namespace OTGS\Toolset\Types\Controller\Interop\Managed\WPML;

use OTGS\Toolset\Types\Controller\Interop\HandlerInterface2;
use OTGS\Toolset\Types\Field\Group\PostGroupViewmodel;
use OTGS\Toolset\Types\Field\Group\TermGroupViewmodel;
use OTGS\Toolset\Types\Field\Group\UserGroupViewmodel;
use Toolset_Field_Utils;

class FieldsGroups implements HandlerInterface2 {

	const PACKAGE_KIND             = 'Toolset Types %s Fields Group';
	const PACKAGE_KIND_SLUG        = 'toolset-types-%s-fields-group';
	const PACKAGE_CONTEXT_TEMPLATE = 'toolset-types-%s-fields-group-%d';
	const PACKAGE_NAME_TEMPLATE    = '%d';

	const FIELDS_CONTEXT = 'plugin Types';

	const DOMAINS = [
		'posts' => 'Posts',
		'terms' => 'Terms',
		'users' => 'Users',
	];

	const ADMIN_URL = 'admin.php?page=%s&group_id=%d';

	const FIELD_GROUP_LABEL = 'Field';
	const FIELD_GROUP_SLUG  = 'toolset-types-field-%s';

	/** @var array */
	private $fieldsByContextToRestore = [];

	public function initialize() {
		// Include strings from ST into field group packages.
		add_filter( 'wpml_translation_package_by_language', [ $this, 'addStringsToTranslationPackage' ], 10, 2 );

		// Save translations to strings that were injected into field groups packages:
		// - switch the context of those strings to the one in the fields group package.
		// - WPML will fill the translation values.
		// - restore the context of the affected strings.
		// We must use this workaround until wpmldev-3286/ is approved and merged.
		add_action( 'wpml_save_external', [ $this, 'savePackageTranslations' ], 9, 2 );
		add_action( 'wpml_save_external', [ $this, 'restoreFieldsContext' ], 11 );

		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'addGroupsAndLabels' ], 10, 2 );

		add_action( 'wpcf_field_group_renamed', [ $this, 'rename' ], 10, 2 );

		add_action( 'wpcf_post_fields_group_delete', [ $this, 'deletePostGroup'] );
		add_action( 'wpcf_term_fields_group_delete', [ $this, 'deleteTermGroup'] );
		add_action( 'wpcf_user_fields_group_delete', [ $this, 'deleteUserGroup'] );
	}

	/**
	 * @param string $domain
	 * @param int    $groupId
	 *
	 * @return string
	 */
	public static function getPackageUrl( $domain, $groupId ) {
		$groupId = intval( $groupId );
		switch ( $domain ) {
			case Toolset_Field_Utils::DOMAIN_TERMS:
				return sprintf( self::ADMIN_URL, TermGroupViewmodel::EDIT_PAGE_SLUG, $groupId );
			case Toolset_Field_Utils::DOMAIN_USERS:
				return sprintf( self::ADMIN_URL, UserGroupViewmodel::EDIT_PAGE_SLUG, $groupId );
			default:
				return sprintf( self::ADMIN_URL, PostGroupViewmodel::EDIT_PAGE_SLUG, $groupId );
		}
	}

	/**
	 * @param string $domain
	 * @param int    $groupId
	 *
	 * @return string
	 */
	public static function getPackageContext( $domain, $groupId ) {
		return sprintf( self::PACKAGE_CONTEXT_TEMPLATE, $domain, $groupId );
	}

	/**
	 * @param int $groupId
	 *
	 * @return string
	 */
	public static function getPackageName( $groupId ) {
		return sprintf( self::PACKAGE_NAME_TEMPLATE, $groupId );
	}

	/**
	 * @param string $domain
	 *
	 * @return string
	 */
	public static function getPackageKind( $domain ) {
		return sprintf( self::PACKAGE_KIND, self::DOMAINS[ $domain ] );
	}

	/**
	 * @param string $domain
	 *
	 * @return string
	 */
	public static function getPackageKindSlug( $domain ) {
		return sprintf( self::PACKAGE_KIND_SLUG, $domain );
	}

	/**
	 * @param string $domain
	 * @param int    $groupId
	 * @param string $groupName
	 *
	 * @return array
	 */
	public static function getPackage( $domain, $groupId, $groupName ) {
		return [
			'kind'      => self::getPackageKind( $domain ),
			'kind_slug' => self::getPackageKindSlug( $domain ),
			'name'      => self::getPackageName( $groupId ),
			'title'     => $groupName,
			'edit_link' => self::getPackageUrl( $domain, $groupId ),
		];
	}

	/**
	 * @param int    $groupId
	 * @param string $groupPackageKindSlug
	 *
	 * @return array
	 */
	private function getFields( $groupId, $groupPackageKindSlug ) {
		switch ( $groupPackageKindSlug ) {
			case 'toolset-types-posts-fields-group':
				return wpcf_admin_fields_get_fields_by_group(
					$groupId, 'slug', false, true, false,
					\Toolset_Field_Group_Post::POST_TYPE,
					\Toolset_Field_Definition_Factory_Post::FIELD_DEFINITIONS_OPTION
				);
			case 'toolset-types-terms-fields-group':
				return wpcf_admin_fields_get_fields_by_group(
					$groupId, 'slug', false, true, false,
					\Toolset_Field_Group_Term::POST_TYPE,
					\Toolset_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
				);
			case 'toolset-types-users-fields-group':
				return wpcf_admin_fields_get_fields_by_group(
					$groupId, 'slug', false, true, false,
					\Toolset_Field_Group_User::POST_TYPE,
					\Toolset_Field_Definition_Factory_User::FIELD_DEFINITIONS_OPTION
				);
			default:
				return [];
		}
	}

	/**
	 * @param array  $fields
	 * @param string $groupPackageKindSlug
	 * @param array  $package
	 */
	private function addFieldsToPackage( $fields, $groupPackageKindSlug, &$package ) {
		array_walk( $fields, function( $field, $fieldId ) use ( $groupPackageKindSlug, &$package ) {
			if (
				$fieldId === $field
				&& '_repeatable_group_' === substr( $fieldId, 0, strlen( '_repeatable_group_' ) )
			) {
				$repeatableGroupId     = (int) str_replace( '_repeatable_group_', '', $fieldId );
				$repeatableGroupFields = $this->getFields( $repeatableGroupId, $groupPackageKindSlug );
				$this->addFieldsToPackage( $repeatableGroupFields, $groupPackageKindSlug, $package );
				return;
			}
			$fieldPieces = [
				'name'          => toolset_getarr( $field, 'name', '' ),
				'description'   => toolset_getarr( $field, 'description', '' ),
				'placeholder'   => toolset_getnest( $field, ['data', 'placeholder'], '' ),
				'default value' => toolset_getnest( $field, ['data', 'user_default_value'], '' ),
			];

			foreach ( $fieldPieces as $pieceName => $pieceValue ) {
				if ( '' !== $pieceValue ) {
					$package['contents'][ 'field ' . $fieldId . ' ' . $pieceName ] = [
						'translate' => 1,
						'data'      => base64_encode( $pieceValue ),
						'format'    => 'base64',
					];
				}
			}

			// Options
			$fieldOptions = toolset_getnest( $field, ['data', 'options'], [] );
			foreach ( $fieldOptions as $name => $option ) {
				if ( 'default' === $name ) {
					continue;
				}
				$fieldOptionsPieces = [
					'title'         => toolset_getarr( $option, 'title', '' ),
					'value'         => wpcf_wpml_field_is_translated( $field ) ? toolset_getarr( $option, 'value', '' ) : '',
					'display value' => toolset_getarr( $option, 'display_value', '' ),
				];
				foreach ( $fieldOptionsPieces as $optionPieceName => $optionPieceValue ) {
					if ( '' !== $optionPieceValue ) {
						$package['contents'][ 'field ' . $fieldId . ' option ' . $name . ' ' . $optionPieceName ] = [
							'translate' => 1,
							'data'      => base64_encode( $optionPieceValue ),
							'format'    => 'base64',
						];
					}
				}

				if ( $field['type'] == 'checkboxes' ) {
					$package['contents'][ 'field ' . $fieldId . ' option ' . $name . ' value' ] = [
						'translate' => 1,
						'data'      => base64_encode( toolset_getarr( $option, 'set_value', '' ) ),
						'format'    => 'base64',
					];
					$package['contents'][ 'field ' . $fieldId . ' option ' . $name . ' display value selected' ] = [
						'translate' => 1,
						'data'      => base64_encode( toolset_getarr( $option, 'display_value_selected', '' ) ),
						'format'    => 'base64',
					];
					$package['contents'][ 'field ' . $fieldId . ' option ' . $name . ' display value not selected' ] = [
						'translate' => 1,
						'data'      => base64_encode( toolset_getarr( $option, 'display_value_not_selected', '' ) ),
						'format'    => 'base64',
					];
				}
			}

			// Checkbox
			if ( 'checkbox' === toolset_getarr( $field, 'type' ) ) {
				$package['contents'][ 'field ' . $fieldId . ' checkbox value' ] = [
					'translate' => 1,
					'data'      => base64_encode( toolset_getnest( $field, ['data', 'set_value'], '' ) ),
					'format'    => 'base64',
				];
				$package['contents'][ 'field ' . $fieldId . ' checkbox value selected' ] = [
					'translate' => 1,
					'data'      => base64_encode( toolset_getnest( $field, ['data', 'display_value_selected'], '' ) ),
					'format'    => 'base64',
				];
				$package['contents'][ 'field ' . $fieldId . ' checkbox value not selected' ] = [
					'translate' => 1,
					'data'      => base64_encode( toolset_getnest( $field, ['data', 'display_value_not_selected'], '' ) ),
					'format'    => 'base64',
				];
			}

			// Validation messages
			$fieldValidationMethods = toolset_getnest( $field, ['data', 'validate'], [] );
			foreach ( $fieldValidationMethods as $method => $validation ) {
				if ( ! empty( $validation['message'] ) && $validation['message'] !== wpcf_admin_validation_messages( $method ) ) {
					$package['contents'][ 'field ' . $fieldId . ' validation message ' . $method ] = [
						'translate' => 1,
						'data'      => base64_encode( toolset_getarr( $validation, 'message', '' ) ),
						'format'    => 'base64',
					];
				}
			}
		} );
	}

	/**
	 * @param array                        $package
	 * @param \WP_Post|\WPML_Package|mixed $post
	 *
	 * @return array
	 */
	public function addStringsToTranslationPackage( $package, $post ) {
		if ( ! $post instanceof \WPML_Package ) {
			return $package;
		}
		if ( ! property_exists( $post, 'kind_slug' ) || ! property_exists( $post, 'name' ) ) {
			return $package;
		}
		if ( ! in_array( $post->kind_slug, [
			'toolset-types-posts-fields-group',
			'toolset-types-terms-fields-group',
			'toolset-types-users-fields-group',
		], true ) ) {
			return $package;
		}

		$groupId = $post->name;
		$fields  = $this->getFields( $groupId, $post->kind_slug );

		if ( empty( $fields ) ) {
			return $package;
		}

		$foo = 'bar';
		$this->addFieldsToPackage( $fields, $post->kind_slug, $package );

		return $package;
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

		return in_array( $job->original_post_type, [
			'package_toolset-types-posts-fields-group',
			'package_toolset-types-terms-fields-group',
			'package_toolset-types-users-fields-group',
		], true );
	}

	/**
	 * @param string    $elementTypePrefix
	 * @param \stdClass $job
	 */
	public function savePackageTranslations( $elementTypePrefix, $job ) {
		if ( 'package' !== $elementTypePrefix ) {
			return;
		}

		if ( ! $this->isOurJob( $job ) ) {
			return;
		}

		global $wpdb;

		$elementTypePrefix = apply_filters( 'wpml_get_package_type_prefix', $elementTypePrefix, $job->original_doc_id );
		foreach ( $job->elements as $field ) {
			if ( ! $field->field_translate ) {
				continue;
			}

			if ( 'field ' !== substr( $field->field_type, 0, strlen( 'field ' ) ) ) {
				continue;
			}

			$this->fieldsByContextToRestore[ $field->field_type ] = $elementTypePrefix;

			$update_data  = array(
				'context' => $elementTypePrefix,
			);
			$update_where = array(
				'context' => self::FIELDS_CONTEXT,
				'name'    => $field->field_type,
			);
	
			$wpdb->update(
				$wpdb->prefix . 'icl_strings',
				$update_data,
				$update_where
			);
		}
	}

	public function restoreFieldsContext() {
		global $wpdb;
		foreach ( $this->fieldsByContextToRestore as $name => $context ) {
			$update_data  = array(
				'context' => self::FIELDS_CONTEXT,
			);
			$update_where = array(
				'context' => $context,
				'name'    => $name,
			);
	
			$wpdb->update(
				$wpdb->prefix . 'icl_strings',
				$update_data,
				$update_where
			);
		}

		$contextsToUpdate = array_values( $this->fieldsByContextToRestore );
		foreach ( $contextsToUpdate as $contextToUpdate ) {
			do_action( 'wpml_st_refresh_domain', $contextToUpdate );
		}
		do_action( 'wpml_st_refresh_domain', self::FIELDS_CONTEXT );
		$this->fieldsByContextToRestore = [];
	}

	/**
	 * @param string $jobOriginalPostType
	 *
	 * return @array
	 */
	private function getGroupFromJob( $jobOriginalPostType ) {
		switch ( $jobOriginalPostType ) {
			case 'package_toolset-types-terms-fields-group':
				return [
					sprintf( self::PACKAGE_KIND_SLUG, 'terms' ) => 'Fields',
				];
			case 'package_toolset-types-users-fields-group':
				return [
					sprintf( self::PACKAGE_KIND_SLUG, 'users' ) => 'Fields',
				];
			default:
			return [
				sprintf( self::PACKAGE_KIND_SLUG, 'posts' ) => 'Fields',
			];
		}
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

		$topLevelGroup = $this->getGroupFromJob( $job->original_post_type );

		foreach ( $fields as &$field ) {
			$field = $this->processField( $field, $topLevelGroup );
		}

		return $fields;
	}

	/**
	 * @param array $field
	 *
	 * @return array|null
	 */
	private function getFieldInfo( $field ) {
		$fieldType = toolset_getarr( $field, 'field_type', '' );

		$contextPieces = explode( ' ', $fieldType );
		if ( count( $contextPieces ) < 2 ) {
			return null;
		}

		$fieldTitlePieces = array_slice( $contextPieces, 2 );
		if ( count( $fieldTitlePieces ) > 1 && in_array( $fieldTitlePieces[0], [ 'option', 'checkbox' ], true ) ) {
			array_splice( $fieldTitlePieces, 1, 1 );
		}

		return [
			'fieldKind'  => $contextPieces[0],
			'fieldSlug'  => $contextPieces[1],
			'fieldTitle' => implode( ' ', $fieldTitlePieces ),
		];
	}

	/**
	 * @param array $field
	 * @param array $topLevelGroup
	 *
	 * @return array
	 */
	public function processField( $field, $topLevelGroup ) {
		$fieldInfo = $this->getFieldInfo( $field );
		if ( ! $fieldInfo ) {
			return $field;
		}

		switch ( $fieldInfo['fieldKind'] ) {
			case 'group':
				$field['title'] = $fieldInfo['fieldTitle'];
				$field['group'] = $topLevelGroup;
				return $field;
			case 'field':
				$field['title'] = $fieldInfo['fieldTitle'];
				$fieldGroup = $topLevelGroup;
				$fieldGroup[ sprintf( self::FIELD_GROUP_SLUG, $fieldInfo['fieldSlug'] )] = self::FIELD_GROUP_LABEL;
				$field['group'] = $fieldGroup;
				return $field;
		}

		return $field;
	}

	/**
	 * The renaming mechanism takes care of adjusting slugs when changing the group name.
	 * No further action needed, group slugs are not editable by themselves over the GUI.
	 *
	 * @param string $newSlug
	 * @param string $oldSlug
	 */
	public function rename( $newSlug, $oldSlug ) {
		global $wpdb;

		$update_data  = array(
			'name' => sprintf( \Types_Wpml_Field_Group_String_Name::DB_NAME_PATTERN, $newSlug ),
		);
		$update_where = array(
			'name' => sprintf( \Types_Wpml_Field_Group_String_Name::DB_NAME_PATTERN, $oldSlug ),
		);

		$wpdb->update(
			$wpdb->prefix . 'icl_strings',
			$update_data,
			$update_where
		);

		$update_data  = array(
			'name' => sprintf( \Types_Wpml_Field_Group_String_Description::DB_NAME_PATTERN, $newSlug ),
		);
		$update_where = array(
			'name' => sprintf( \Types_Wpml_Field_Group_String_Description::DB_NAME_PATTERN, $oldSlug ),
		);

		$wpdb->update(
			$wpdb->prefix . 'icl_strings',
			$update_data,
			$update_where
		);
	}

	/**
	 * @param \Toolset_Field_Group $group
	 */
	public function deletePostGroup( $group ) {
		$this->delete( $group, 'posts' );
	}

	/**
	 * @param \Toolset_Field_Group $group
	 */
	public function deleteTermGroup( $group ) {
		$this->delete( $group, 'terms' );
	}

	/**
	 * @param \Toolset_Field_Group $group
	 */
	public function deleteUserGroup( $group ) {
		$this->delete( $group, 'users' );
	}

	/**
	 * @param \Toolset_Field_Group $group
	 * @param string               $domain
	 */
	private function delete( $group, $domain ) {
		do_action(
			'wpml_delete_package',
			sprintf( self::PACKAGE_NAME_TEMPLATE, $group->get_id() ),
			$this->getPackageKind( $domain )
		);
	}

}
