<?php

namespace OTGS\Toolset\Types\Upgrade\Commands;

use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Common\Result\Success;
use OTGS\Toolset\Common\Upgrade\UpgradeCommand;
use OTGS\Toolset\Types\Controller\Interop\Managed\WPML\FieldsGroups;
use OTGS\Toolset\Types\Controller\Interop\Managed\WPML\PostTypes;
use OTGS\Toolset\Types\Controller\Interop\Managed\WPML\Taxonomies;
use \Toolset_Condition_Plugin_Wpml_String_Translation_Is_Active;
use \Toolset_Field_Utils;
use \Types_Utils_Post_Type_Option;

/**
 * Upgrade database to 306000 (Types 3.6.0).
 *
 * - toolsetga-252 Use WPML Tranbslation Packages.
 * - toolsetga-252 ATE gen3 labels and groups.
 *
 * @codeCoverageIgnore Production-tested and not to be touched.
 */
class Upgrade3060000TranslationPackages implements UpgradeCommand {

	const OLD_CONTEXT_CPT    = 'Types-CPT';
	const OLD_CONTEXT_TAX    = 'Types-TAX';
	const OLD_CONTEXT_GROUPS = 'plugin Types';

	/** @var \wpdb */
	private $wpdb;

	/** @var array */
	private $stringContexts = [];

	/** @var array */
	private $stringNames = [];

	/** @var array */
	private $packages = [];

	/** @var array|null */
	private $groupsData = null;

	public function run() {
		$condition = new Toolset_Condition_Plugin_Wpml_String_Translation_Is_Active();
		if ( ! $condition->is_met() ) {
			return new Success();
		}

		global $wpdb;
		$this->wpdb = $wpdb;

		$this->processPostTypes();
		$this->processTaxonomies();
		$this->processGroups();
		$this->applyChanges();

		return new Success();
	}

	/**
	 * @param string $key
	 */
	private function ensureStringContextList( $key ) {
		if ( ! array_key_exists( $key, $this->stringContexts ) ) {
			$this->stringContexts[ $key ] = [];
		}
	}

	/**
	 * @param string $context
	 * @param int    $id
	 */
	private function recordStringContextForId( $context, $id ) {
		$this->ensureStringContextList( $context );
		$this->stringContexts[ $context ][] = intval( $id );
	}

	private function checkEncodedName( $storedString, $labels, $type ) {
		if ( null === $type ) {
			return;
		}
		if ( array_key_exists( $storedString['id'], $this->stringNames ) ) {
			// Some taxonomies might have the same name and singular_name: do not update the same first entry twice.
			return;
		}
		if ( $storedString['name'] !== md5( $storedString['value'] ) ) {
			return;
		}
		if ( $labels['name'] === $storedString['value'] ) {
			$this->stringNames[ $storedString['id'] ] = $type . ' name';
			return;
		}
		if ( $labels['singular_name'] === $storedString['value'] ) {
			$this->stringNames[ $storedString['id'] ] = $type . ' singular_name';
			return;
		}
	}

	/**
	 * @param string      $context
	 * @param array[]     $registeredStrings
	 * @param string[]    $labels
	 * @param string|null $type
	 */
	private function recordRegisteredStrings( $context, $registeredStrings, $labels, $type = null ) {
		foreach ( $registeredStrings as $storedString ) {
			if ( in_array( $storedString['value'], $labels, true ) ) {
				$this->recordStringContextForId( $context, $storedString['id'] );
				$this->checkEncodedName( $storedString, $labels, $type );
				continue;
			}
		}
	}

	/**
	 * @param string $context
	 * @param string $kind
	 * @param string $name
	 * @param string $title
	 * @param string $editLink
	 */
	private function recordPackage( $context, $kind, $name, $title, $editLink = '' ) {
		if ( array_key_exists( $context, $this->packages ) ) {
			return;
		}
		$this->packages[ $context ] = [
			'kind'      => $kind,
			'kind_slug' => sanitize_title( $kind ),
			'name'      => $name,
			'title'     => $title,
			'edit_link' => $editLink,
		];
	}

	private function processPostTypes() {
		$registeredStrings = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT * FROM {$this->wpdb->prefix}icl_strings
				WHERE context = %s
				",
				self::OLD_CONTEXT_CPT
			),
			ARRAY_A
		);
		if ( empty( $registeredStrings ) ) {
			return;
		}

		$objectDefaults       = wpcf_custom_types_default();
		$defaultLabels        = $objectDefaults['labels'];
		$defaultLabelsContext = PostTypes::DEFAULT_PACKAGE_CONTEXT;
		$this->recordRegisteredStrings( $defaultLabelsContext, $registeredStrings, $defaultLabels );
		$this->recordPackage( $defaultLabelsContext, PostTypes::PACKAGE_KIND, PostTypes::DEFAULT_PACKAGE_NAME, PostTypes::DEFAULT_PACKAGE_TITLE );

		$postTypeOption = new Types_Utils_Post_Type_Option();
		$customTypes    = $postTypeOption->get_post_types();
		foreach ( $customTypes as $type => $data ) {
			if ( empty( $data ) ) {
					continue;
			}
			if (
					( isset( $data['_builtin'] ) && $data['_builtin'] )
					|| wpcf_is_builtin_post_types( $type )
			) {
					continue;
			}
			$labels  = toolset_getarr( $data, 'labels', [] );
			$labels  = array_diff( $labels, $defaultLabels );
			if ( empty( $labels ) ) {
				continue;
			}

			$context = PostTypes::getPackageContext( $type );
			$this->recordRegisteredStrings( $context, $registeredStrings, $labels, $type );
			$this->recordPackage(
				$context,
				PostTypes::PACKAGE_KIND,
				PostTypes::getPackageName( $type ),
				toolset_getarr( $labels, 'name', $type ),
				PostTypes::getAdminUrl( $type )
			);
		}
	}

	private function processTaxonomies() {
		$registeredStrings = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT * FROM {$this->wpdb->prefix}icl_strings
				WHERE context = %s
				",
				self::OLD_CONTEXT_TAX
			),
			ARRAY_A
		);
		if ( empty( $registeredStrings ) ) {
			return;
		}

		$objectDefaults       = wpcf_custom_taxonomies_default();
		$defaultLabels        = $objectDefaults['labels'];
		$defaultLabelsContext = Taxonomies::DEFAULT_PACKAGE_CONTEXT;
		$this->recordRegisteredStrings( $defaultLabelsContext, $registeredStrings, $defaultLabels );
		$this->recordPackage( $defaultLabelsContext, Taxonomies::PACKAGE_KIND, Taxonomies::DEFAULT_PACKAGE_NAME, Taxonomies::DEFAULT_PACKAGE_TITLE );

		$objectsLabels = [];
		$customTypes   = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
		foreach ( $customTypes as $type => $data ) {
			if ( empty( $data ) ) {
					continue;
			}
			if ( isset( $data['_builtin'] ) && $data['_builtin'] ) {
					continue;
			}
			$labels  = toolset_getarr( $data, 'labels', [] );
			$labels  = array_diff( $labels, $defaultLabels );
			if ( empty( $labels ) ) {
				continue;
			}

			$context = Taxonomies::getPackageContext( $type );
			$this->recordRegisteredStrings( $context, $registeredStrings, $labels, $type );
			$this->recordPackage(
				$context,
				Taxonomies::PACKAGE_KIND,
				Taxonomies::getPackageName( $type ),
				toolset_getarr( $labels, 'name', $type ),
				Taxonomies::getAdminUrl( $type )
			);
		}
	}

	/**
	 * @param string $context
	 *
	 * @return string|null
	 */
	private function getSlug( $context ) {
		$contextPieces = explode( ' ', $context );
		if ( count( $contextPieces ) > 1 ) {
			return $contextPieces[1];
		}
		return null;
	}

	/**
	 * @return array
	 */
	private function generateGroupsData() {
		$groupsByDomain = [
			Toolset_Field_Utils::DOMAIN_POSTS => wpcf_admin_fields_get_groups( \Toolset_Field_Group_Post::POST_TYPE ),
			Toolset_Field_Utils::DOMAIN_TERMS => wpcf_admin_fields_get_groups( \Toolset_Field_Group_Term::POST_TYPE ),
			Toolset_Field_Utils::DOMAIN_USERS => wpcf_admin_fields_get_groups( \Toolset_Field_Group_User::POST_TYPE ),
		];
		$groupsData = [];
		foreach ( $groupsByDomain as $domain => $groups ) {
			array_walk( $groups, function( $group, $groupKey ) use ( $domain, &$groupsData ) {
				// Warning! Note that two groups on different domains can share slug!
				// With ST, translations would be applied to all of them,
				// but with packages, identifyed by object ID, only the last processed one will get translated.
				$groupsData[ $group['slug'] ] = [
					'domain' => $domain,
					'id'     => $group['id'],
					'slug'   => $group['slug'],
					'title'  => $group['name'],
				];
			} );
		}
		return $groupsData;
	}

	/**
	 * @param string $groupSlug
	 *
	 * @return array|null
	 */
	private function getGroupData( $groupSlug ) {
		if ( null === $this->groupsData ) {
			$this->groupsData = $this->generateGroupsData();
		}
		if ( ! array_key_exists( $groupSlug, $this->groupsData ) ) {
			return null;
		}
		return $this->groupsData[ $groupSlug ];
	}

	private function processGroups() {
		$registeredStrings = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT * FROM {$this->wpdb->prefix}icl_strings
				WHERE context = %s
				AND name LIKE %s
				",
				self::OLD_CONTEXT_GROUPS,
				'group %'
			),
			ARRAY_A
		);

		if ( empty( $registeredStrings ) ) {
			return;
		}

		foreach ( $registeredStrings as $storedString ) {
			if ( 'group ' !== substr( $storedString['name'], 0, 6 ) ) {
				continue;
			}

			$groupSlug = $this->getSlug( $storedString['name'] );
			if ( null === $groupSlug ) {
				continue;
			}

			$groupData = $this->getGroupData( $groupSlug );
			if ( null === $groupData ) {
				continue;
			}

			$context = FieldsGroups::getPackageContext( $groupData['domain'], $groupData['id'] );
			$this->recordStringContextForId( $context, $storedString['id'] );
			$this->recordPackage(
				$context,
				FieldsGroups::getPackageKind( $groupData['domain'] ),
				FieldsGroups::getPackageName( $groupData['id'] ),
				$groupData['title'],
				FieldsGroups::getPackageUrl( $groupData['domain'], $groupData['id'] )
			);
		}
	}

	private function applyChanges() {
		// Fix taxonomies name and singular_name columns
		foreach( $this->stringNames as $stringId => $stringName ) {
			$this->wpdb->query(
				$this->wpdb->prepare(
					"
					UPDATE {$this->wpdb->prefix}icl_strings
					SET name = %s
					WHERE id = {$stringId}
					",
					$stringName
				)
			);
		}

		// Fix context, name, string package, gettext_context and domain_name_context_md5 columns.
		foreach ( $this->stringContexts as $context => $idList ) {
			$idList = array_unique( $idList );
			if ( empty( $idList ) ) {
				continue;
			}

			$packageId = \WPML_Package_Helper::create_new_package( new \WPML_Package( $this->packages[ $context ] ) );

			$this->wpdb->query(
				$this->wpdb->prepare(
					"
					UPDATE {$this->wpdb->prefix}icl_strings
					SET context = %s,
						name = REPLACE( name, ' ', '-' ),
						string_package_id = %d,
						gettext_context = %s
					WHERE id IN ( " . implode( ',', $idList ) . ' )
					',
					[ $context, $packageId, '' ]
				)
			);

			do_action( 'wpml_st_refresh_domain', $context );
		}

		do_action( 'wpml_st_refresh_domain', self::OLD_CONTEXT_CPT );
		do_action( 'wpml_st_refresh_domain', self::OLD_CONTEXT_TAX );
		do_action( 'wpml_st_refresh_domain', self::OLD_CONTEXT_GROUPS );
	}

}