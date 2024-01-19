<?php

namespace OTGS\Toolset\CRED\Controller;

use OTGS\Toolset\CRED\Controller\Condition\ClassExists;
use OTGS\Toolset\CRED\Controller\Condition\FunctionExists;

use OTGS\Toolset\CRED\Controller\Permissions\Factory as permissionsFactory;

/**
 * Toolset Forms permissions manager.
 *
 * @since 2.1.1
 */
class Permissions {

	/**
	 * @var \Toolset_Condition_Plugin_Access_Active
	 */
	private $toolset_access_condition = null;

	/**
	 * @var ClassExists
	 */
	private $di_class_exists = null;

	/**
	 * @var FunctionExists
	 */
	private $di_function_exists = null;

	/**
	 * @var permissionsFactory
	 */
	private $di_factory = null;

	/**
	 * @var array
	 */
	private $custom_capabilities = array(
		\CRED_Form_Domain::POSTS => array(
			'delete_own_posts_with_cred',
			'delete_other_posts_with_cred',
			'use_any_attachment_with_cred_post_forms',
		),
		\CRED_Form_Domain::USERS => array(
			// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			// 'delete_own_user_with_cred',
			// 'delete_other_users_with_cred',
			'use_any_attachment_with_cred_user_forms',
		),
		\CRED_Form_Domain::ASSOCIATIONS => array(
			'use_any_attachment_with_cred_rel_forms',
		),
	);

	/**
	 * @var array
	 */
	private $custom_capabilities_by_form = array(
		\CRED_Form_Domain::POSTS => array(
			'new' => array(
				'create_posts_with_cred_',
			),
			'edit' => array(
				'edit_own_posts_with_cred_',
				'edit_other_posts_with_cred_',
			),
		),
		\CRED_Form_Domain::USERS => array(
			'new' => array(
				'create_users_with_cred_',
			),
			'edit' => array(
				'edit_own_user_with_cred_',
				'edit_other_users_with_cred_',
			),
		),
		\CRED_Form_Domain::ASSOCIATIONS => array(),
	);

	/**
	 * @var array|null
	 */
	private $built_custom_capabilities = null;

	/**
	 * Constructor.
	 *
	 * @param \Toolset_Condition_Plugin_Access_Active $di_toolset_access_condition
	 * @param ClassExists $di_class_exists
	 * @param FunctionExists $di_function_exists
	 * @param permissionsFactory $di_factory
	 */
	public function __construct(
		\Toolset_Condition_Plugin_Access_Active $di_toolset_access_condition = null,
		ClassExists $di_class_exists = null,
		FunctionExists $di_function_exists = null,
		permissionsFactory $di_factory = null

	) {
		$this->toolset_access_condition = ( $di_toolset_access_condition instanceof \Toolset_Condition_Plugin_Access_Active )
			? $di_toolset_access_condition
			: new \Toolset_Condition_Plugin_Access_Active();

		$this->di_class_exists = ( null === $di_class_exists )
			? new ClassExists()
			: $di_class_exists;

		$this->di_function_exists = ( null === $di_function_exists )
			? new FunctionExists()
			: $di_function_exists;

		$this->di_factory = ( null === $di_factory )
			? new permissionsFactory()
			: $di_factory;
	}

	/**
	 * Generate a valid title for the Toolset Forms custom capabilities.
	 *
	 * @param string $capability
	 * @param string $arg Optional variable particle on the title.
	 * @return string
	 * @since 2.1.1
	 * @since 2.4 Add extra capabilities for media management.
	 */
	public function get_custom_capability_title( $capability, $arg = '' ) {
		$title = '';
		switch ( $capability ) {
			case 'delete_own_posts_with_cred':
				/* translators: Label of the capability to delete your own posts using this plugin */
				return __( 'Delete Own Posts using Toolset Forms', 'wp-cred' );
			case 'delete_other_posts_with_cred':
				/* translators: Label of the capability to delete posts created by others using this plugin */
				return __( 'Delete Others Posts using Toolset Forms', 'wp-cred' );
			case 'delete_own_user_with_cred':// Checked - never used
				/* translators: Label of the capability to delete your own user using this plugin */
				return __( 'Delete Own User using Toolset Forms', 'wp-cred' );
			case 'delete_other_users_with_cred':// Checked - never used
				/* translators: Label of the capability to delete other users using this plugin */
				return __( 'Delete Other Users using Toolset Forms', 'wp-cred' );
			case 'create_posts_with_cred_':
				/* translators: Label of the capability to create a new post with a given form using this plugin */
				return sprintf( __( 'Create Custom Post with the Form "%s"', 'wp-cred' ), $arg );
			case 'edit_own_posts_with_cred_':
				/* translators: Label of the capability to edit your own posts with a given form using this plugin */
				return sprintf( __( 'Edit Own Custom Post with the Form "%s"', 'wp-cred' ), $arg );
			case 'edit_other_posts_with_cred_':
				/* translators: Label of the capability to edit posts created by others with a given form using this plugin */
				return sprintf( __( 'Edit Others Custom Post with the Form "%s"', 'wp-cred' ), $arg );
			case 'create_users_with_cred_':
				/* translators: Label of the capability to create a new user with a given form using this plugin */
				return sprintf( __( 'Create User with the Form "%s"', 'wp-cred' ), $arg );
			case 'edit_own_user_with_cred_':
				/* translators: Label of the capability to edit your own user with a given form using this plugin */
				return sprintf( __( 'Edit Own User with the Form "%s"', 'wp-cred' ), $arg );
			case 'edit_other_users_with_cred_':
				/* translators: Label of the capability to edit other users with a given form using this plugin */
				return sprintf( __( 'Edit Other User with the Form "%s"', 'wp-cred' ), $arg );
			case 'use_any_attachment_with_cred_post_forms':
				/* translators: Label of the capability to use any media file of the site in media fields on post forms */
				return __( 'Use any Media Library file when adding files to front-end Post Forms', 'wp-cred' );
			case 'use_any_attachment_with_cred_user_forms':
				/* translators: Label of the capability to use any media file of the site in media fields on user forms */
				return __( 'Use any Media Library file when adding files to front-end User Forms', 'wp-cred' );
			case 'use_any_attachment_with_cred_rel_forms':
				/* translators: Label of the capability to use any media file of the site in media fields on relationship forms */
				return __( 'Use any Media Library file when adding files to front-end Relationship Forms', 'wp-cred' );
		}
		return $title;
	}

	/**
	 * Initialize the permissions management for Toolset Forms, in a cascading decision tree.
	 *
	 * - First, check whether Toolset Access is instaled, and eventually initialize its
	 *   compatibility layer.
	 * - Otherwise, check whether support for tird parties is needed, and
	 *   eventually initialize it.
	 * - Otherwise, setup our custom capabilities and grant them to the right users.
	 *
	 * @since 2.1.1
	 */
	public function initialize() {
		if ( $this->toolset_access_condition->is_met() ) {
			$toolset_access_compatibility = $this->di_factory->toolset_access( $this );
			$toolset_access_compatibility->initialize();
			return;
		}
		if ( $this->is_third_party_support_needed() ) {
			$third_party_compatibility = $this->di_factory->third_party( $this );
			$third_party_compatibility->initialize();
			return;
		}
		$this->setup_custom_capabilities();
	}

	/**
	 * Check whether third party support for capabilitis is needed.
	 *
	 * @return boolean
	 * @since 2.1.1
	 */
	private function is_third_party_support_needed() {
		return (
			// User Role Editor plugin
			$this->di_function_exists->is_met( 'ure_not_edit_admin' )
			// Members plugin
			|| $this->di_class_exists->is_met( 'Members_Load' )
		);
	}

	/**
	 * Setup our custom capabilities in a generic way.
	 *
	 * @since 2.1.1
	 */
	private function setup_custom_capabilities() {
		add_filter( 'user_has_cap', array( $this, 'grant_built_capabilities' ), 5, 3 );
	}

	/**
	 * Grant custom capabilities to the right users.
	 *
	 * Although we grant those capabilities here, we do not really support them later in all cases.
	 * User forms have additional checks besides capabilities.
	 *
	 * @param array $allcaps
	 * @param array $caps
	 * @param array $args
	 * @return array
	 * @todo Finetune this here instead, or too.
	 * @since 2.1.1
	 */
	public function grant_built_capabilities(
		$allcaps,
		$caps,
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$args
	) {
		if ( empty( $caps ) ) {
			return $allcaps;
		}

		if ( strpos( $caps[0], 'with_cred' ) === false ) {
			return $allcaps;
		}

		$custom_capabilities = $this->get_built_custom_capabilities();
		foreach ( $custom_capabilities[ \CRED_Form_Domain::POSTS ] as $custom_cap ) {
			$allcaps[ $custom_cap ] = $this->get_post_forms_default_built_capability_value( $custom_cap );
		}
		foreach ( $custom_capabilities[ \CRED_Form_Domain::USERS ] as $custom_cap ) {
			$allcaps[ $custom_cap ] = $this->get_user_forms_default_built_capability_value( $custom_cap );
		}
		// TODO do not leave it here this way: set defaults based on current capabilities
		foreach ( $custom_capabilities[ \CRED_Form_Domain::ASSOCIATIONS ] as $custom_cap ) {
			$allcaps[ $custom_cap ] = $this->get_rel_forms_default_built_capability_value( $custom_cap );
		}

		return $allcaps;
	}

	/**
	 * Get the default value for each post forms capability.
	 *
	 * @param string $custom_cap
	 * @return bool
	 * @see The note on grant_built_capabilities.
	 * @since 2.4
	 */
	private function get_post_forms_default_built_capability_value( $custom_cap ) {
		switch ( $custom_cap ) {
			case 'use_any_attachment_with_cred_post_forms':
				return current_user_can( 'edit_published_posts' );
		}
		return true;
	}

	/**
	 * Get the default value for each user forms capability.
	 *
	 * @param string $custom_cap
	 * @return bool
	 * @see The note on grant_built_capabilities.
	 * @since 2.4
	 */
	private function get_user_forms_default_built_capability_value( $custom_cap ) {
		switch ( $custom_cap ) {
			case 'use_any_attachment_with_cred_user_forms':
				return current_user_can( 'edit_published_posts' );
		}
		return true;
	}

	/**
	 * Get the default value for each relationship forms capability.
	 *
	 * @param string $custom_cap
	 * @return bool
	 * @see The note on grant_built_capabilities.
	 * @since 2.4
	 */
	private function get_rel_forms_default_built_capability_value( $custom_cap ) {
		switch ( $custom_cap ) {
			case 'use_any_attachment_with_cred_rel_forms':
				return current_user_can( 'edit_published_posts' );
		}
		return true;
	}

	/**
	 * Get, or generate and return, the whole set of custom capabilities managed by Toolset Forms.
	 *
	 * Toolset Forms registers a series of static capabilities, to delete own and other posts,
	 * and to delete own and other users.
	 *
	 * It also generates a number of capabilities that depend on form IDs and the form domain and usage
	 * (create new content or edit eisting content).
	 *
	 * @return array
	 * @since 2.1.1
	 */
	public function get_built_custom_capabilities() {
		if ( ! is_null( $this->built_custom_capabilities ) ) {
			return $this->built_custom_capabilities;
		}

		$this->built_custom_capabilities = array(
			\CRED_Form_Domain::POSTS => $this->generate_custom_capabilities_by_domain( \CRED_Form_Domain::POSTS ),
			\CRED_Form_Domain::USERS => $this->generate_custom_capabilities_by_domain( \CRED_Form_Domain::USERS ),
			\CRED_Form_Domain::ASSOCIATIONS => $this->generate_custom_capabilities_by_domain( \CRED_Form_Domain::ASSOCIATIONS ),
		);

		return $this->built_custom_capabilities;
	}

	/**
	 * Get the list of static capabilities not depending on form IDs, but still classified by domain.
	 *
	 * @return array
	 * @since 2.1.1
	 */
	public function get_custom_capabilities() {
		return $this->custom_capabilities;
	}

	/**
	 * Get the list of prefixes for dynamic capabilities that depend on domain, usage and form IDs.
	 *
	 * @return array
	 * @since 2.1.1
	 */
	public function get_custom_capabilities_by_form() {
		return $this->custom_capabilities_by_form;
	}

	/**
	 * Generate custom capabilities given a list of forms and capability prefixes.
	 *
	 * @param array $built Already built capabilities.
	 * @param array $forms List of forms, as objects with properties ID, post_title, post_name.
	 * @param array $cap_prefixes List of prefixes for capabilities.
	 * @return array
	 * @since 2.1.1
	 */
	private function generate_custom_capabilities_by_form_and_prefix( $built, $forms, $cap_prefixes ) {
		foreach ( $forms as $form ) {
			foreach ( $cap_prefixes as $cap_prefix ) {
				$built[] = $cap_prefix . $form->ID;
			}
		}

		return $built;
	}

	/**
	 * Generate custom capabilities given a domain.
	 *
	 * @param string $domain
	 * @return array
	 * @since 2.1.1
	 */
	private function generate_custom_capabilities_by_domain( $domain ) {
		if ( ! in_array( $domain, array( \CRED_Form_Domain::POSTS, \CRED_Form_Domain::USERS, \CRED_Form_Domain::ASSOCIATIONS ), true ) ) {
			return array();
		}

		$custom_capabilities = $this->get_custom_capabilities();
		$built = $custom_capabilities[ $domain ];

		if ( in_array( $domain, array( \CRED_Form_Domain::ASSOCIATIONS ), true ) ) {
			// Relationship forms do not have custom capabilities per form - yet
			// Also, the generated cache that cred_get_available_forms returns has a different format
			return $built;
		}

		$existing_forms = apply_filters( 'cred_get_available_forms', array(), $domain );
		$existing_forms_new = toolset_getarr( $existing_forms, 'new', array() );
		$existing_forms_edit = toolset_getarr( $existing_forms, 'edit', array() );

		$custom_capabilities_by_form = $this->get_custom_capabilities_by_form();
		$custom_capabilities_by_form_and_domain = toolset_getarr( $custom_capabilities_by_form, $domain, array() );

		$built = $this->generate_custom_capabilities_by_form_and_prefix( $built, $existing_forms_new, $custom_capabilities_by_form_and_domain['new'] );
		$built = $this->generate_custom_capabilities_by_form_and_prefix( $built, $existing_forms_edit, $custom_capabilities_by_form_and_domain['edit'] );

		return $built;
	}

}
