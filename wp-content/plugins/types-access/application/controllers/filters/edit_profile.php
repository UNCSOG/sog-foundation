<?php

namespace OTGS\Toolset\Access\Controllers\Filters;

use OTGS\Toolset\Common\WpUserFactory;

/**
 * Class allow a user to select multiple roles for an edited user
 *
 * @package OTGS\Toolset\Access\Controllers\Filters
 *
 * @since 2.8
 */
class EditProfile {

	/**
	 * @var object
	 */
	private static $instance;

	/** @var WpUserFactory */
	protected $wp_user_factory;

	/**
	 * @return object|EditProfile
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
	 * EditProfile constructor.
	 *
	 * @param WpUserFactory|null $wp_user_factory
	 */
	public function __construct( WpUserFactory $wp_user_factory = null ) {
		$this->wp_user_factory = $wp_user_factory ?: new WpUserFactory;
	}

	/**
	 * Assign additional roles to a user
	 */
	public function save_user_roles() {

		if ( isset( $_POST['access_multiple_roles'] ) && ! empty( $_POST['access_multiple_roles'] ) ) {
			if ( ! isset( $_POST['user_id'] ) ) {
				return;
			}
			$user_id = intval( $_POST['user_id'] );

			if ( empty( $_POST['user_id'] ) ) {
				return;
			}

			check_admin_referer( 'update-user_' . $user_id );

			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				wp_die( __( 'Sorry, you are not allowed to edit this user.', 'wpcf-access' ) );
			}


			$user = $this->wp_user_factory->load( $user_id );

			$roles = array_map( 'sanitize_key', (array) $_POST['access_multiple_roles'] );

			$editable_roles = get_editable_roles();
			if ( ! empty( $editable_roles[ $_POST['role'] ] ) ) {
				$user->set_role( sanitize_text_field( wp_unslash( $_POST['role'] ) ) );
			}
			foreach ( $roles as $role ) {
				if ( ! empty( $editable_roles[ $role ] ) ) {
					$user->add_role( sanitize_text_field( $role ) );
				}
			}
			do_action( 'taccess_after_update_profile', $user_id, $roles, $user->roles );
		}
	}

	/**
	 * Enqueue JS with a form to assign multiple roles
	 *
	 * @param object $user
	 * @param string $path
	 */
	public function add_roles_area( $user, $path = TACCESS_PLUGIN_URL ) {
		$multiple_roles_doc_link = 'https://toolset.com/course-lesson/setting-up-custom-roles-for-members/?utm_source=plugin&utm_medium=gui&utm_campaign=access';
		$localization_data = array(
			'roles'                => wp_json_encode( array_flip( $user->roles ) ),
			'additional_roles'     => __( 'Additional roles:', 'wpcf-access' ),
			'more_roles_link_text' => __( 'I want to add more roles to this user', 'wpcf-access' ),
			'disabled_role_text'   => __( 'This role is already selected as the main one.', 'wpcf-access' ),
			'multi_roles_notes'    =>  sprintf( __( 'Learn more about <a href="%s">using multiple roles per user</a>', 'wpcf-access' ), $multiple_roles_doc_link ),
		);
		wp_register_script( 'taccess-edit-profile', $path . '/assets/js/edit_profile.js', array( 'jquery' ), TACCESS_VERSION, false );
		wp_localize_script( 'taccess-edit-profile', 'taccess_edit_profile_strings', $localization_data );
		wp_enqueue_script( 'taccess-edit-profile' );

	}

}
