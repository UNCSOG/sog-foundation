<?php

namespace OTGS\Toolset\CRED\Controller\Frontend;

/**
 * Media manager main controller.
 *
 * Provides the right capabilities to logged in users so they can use
 * the frontend media manager modals.
 *
 * Performs related operations for media modals filtering.
 *
 * @since 2.4
 */
class MediaManager {

	/**
	 * @var array
	 */
	private $temporary_caps = array();

	/**
	 * @var \Toolset_Constants
	 */
	private $toolset_constants = null;

	/**
	 * @var \OTGS\Toolset\CRED\Model\Wordpress\Media
	 */
	private $media_model = null;

	// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
	/**
	 * @var string[]
	 */
	private $caps_to_skip_mapping = array(
		// Core capabilities
		'create_sites', 'delete_sites',
		'update_core',
		'setup_network', 'manage_network', 'upgrade_network',
		'manage_sites', 'delete_site',
		'manage_network_users', 'manage_network_plugins', 'manage_network_themes', 'manage_network_options',
		'upload_plugins', 'activate_plugins', 'update_plugins',
		'install_plugins', 'edit_plugins', 'delete_plugins',
		'upload_themes', 'switch_themes', 'update_themes',
		'install_themes', 'edit_theme_options', 'edit_themes', 'delete_themes',
		'unfiltered_html',
		'manage_options',
		'list_users', 'create_users',
		'install_languages', 'update_languages',
		'manage_links', 'manage_categories',
		'moderate_comments',
		'do_not_allow',
		// Custom capabilities by Toolset plugins
		'ddl_create_layout', 'ddl_create_content_layout', 'ddl_edit_layout',
	);
	// phpcs:enable

	/**
	 * Constructor
	 *
	 * @param \Toolset_Constants $toolset_constants
	 * @param \OTGS\Toolset\CRED\Model\Wordpress\Media $media_model
	 */
	public function __construct(
		\Toolset_Constants $toolset_constants,
		\OTGS\Toolset\CRED\Model\Wordpress\Media $media_model
	) {
		$this->toolset_constants = $toolset_constants;
		$this->media_model = $media_model;
	}


	/**
	 * Initialize the mechanism to grant capabilities to logged in users
	 * over media files queries and uploads in frontend forms.
	 *
	 * @since 2.4
	 */
	public function initialize() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$this->grant_media_query_and_upload_capabilities();
		$this->maybe_grant_media_metadata_save_capabilities();
	}

	/**
	 * Grant query and upload attachments capabilities for logged in users
	 * when using a Toolset.CRED.FrontendMediaManager instance.
	 *
	 * @since 2.4
	 */
	public function grant_media_query_and_upload_capabilities() {
		if (
			! $this->toolset_constants->defined( 'DOING_AJAX' )
			|| ! $this->toolset_constants->constant( 'DOING_AJAX' )
		) {
			return;
		}

		$current_user = wp_get_current_user();
		if ( 0 > $current_user->ID ) {
			return;
		}

		$current_action = toolset_getpost( 'action' );

		// Each of the supported AJAX actions set different parameters in different POSTed places
		switch ( $current_action ) {
			case 'query-attachments':
				$custom_nonce = toolset_getnest( $_POST, array( 'query', 'toolset_media_management_nonce' ) );
				$current_origin = toolset_getnest( $_POST, array( 'query', 'toolset_media_management_origin' ) );
				break;
			case 'upload-attachment':
				$custom_nonce = toolset_getpost( 'toolset_media_management_nonce' );
				$current_origin = toolset_getpost( 'toolsetOrigin' );
				break;
			default:
				return;
		}

		// Special case for Add media button inside a Forms form: we want to filter its content, but there's no custom
		// nonce to check, it's a WP core dialog.
		if ( 'toolsetFormsAddMedia' !== $current_origin ) {
			if ( 'toolsetForms' !== $current_origin ) {
				return;
			}
			if ( ! wp_verify_nonce( $custom_nonce, 'toolset_media_field_' . $current_user->ID ) ) {
				return;
			}
		}

		// Each of the supported AJAX actions require different capabilities:
		// - query-attachments requires upload_files
		// - upload-attachment requires all of them
		// See wp-admin/includes/ajax-actions.php for reference:
		// - wp_ajax_query_attachments
		// - wp_ajax_upload_attachment
		switch ( $current_action ) {
			case 'query-attachments':
				// Querying requires the ability to upload attachments, for some reason
				$this->grant_user_upload_capability( $current_user );
				// Maybe limit which attachments can be queried by the current user.
				add_filter( 'ajax_query_attachments_args', array( $this, 'filter_query_attachments' ) );
				break;
			case 'upload-attachment':
				// Uploading requires the ability to upload attachments,
				// plus editing capabilities on the post to attach to;
				// for some users, edit_posts is not enough
				// if that post is not owned by the current user.
				$this->grant_user_upload_capability( $current_user );
				$this->grant_user_edit_capability( $current_user );
				$this->grant_user_edit_single_capability( $current_user );
				$this->grant_user_edit_full_capability( $current_user );
				// Maybe validate uploads
				add_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_native_uploads' ) );
				break;
			default:
				return;
		}

		// Remove any third party hooking into the fields of the media modal.
		// Third parties tend to use scripts and assets not available for frontend forms,
		// so we better disable them by design.
		remove_all_filters( 'attachment_fields_to_edit' );

		add_filter( 'user_has_cap', array( $this, 'filter_grant_temporary_capabilities' ), 99, 3 );
		add_action( 'shutdown', array( $this, 'remove_temporary_capabilities' ) );
	}

	/**
	 * Make sure that contributors and other logged in users
	 * without edit_posts capabilities can edit their own
	 * attachments metadata on frontend forms media fields.
	 *
	 * @since 2.4
	 * @note There is no check that this is coming from Toolset Forms, because this
	 *       AJAX action can not be hooked, or extended.
	 */
	public function maybe_grant_media_metadata_save_capabilities() {
		if (
			! $this->toolset_constants->defined( 'DOING_AJAX' )
			|| ! $this->toolset_constants->constant( 'DOING_AJAX' )
		) {
			return;
		}

		$current_user = wp_get_current_user();

		if ( $current_user->has_cap( 'edit_posts' ) ) {
			return;
		}

		if ( 0 > $current_user->ID ) {
			return;
		}

		$current_action = toolset_getpost( 'action' );

		if ( 'save-attachment' !== $current_action ) {
			return;
		}

		$current_attachment = toolset_getpost( 'id' );
		$current_attachment_author_id = (int) get_post_field( 'post_author', $current_attachment );

		if ( $current_attachment_author_id === $current_user->ID ) {
			// Let any logged in user edit the metadata for his own attachments
			$this->grant_user_edit_capability( $current_user );
			$this->grant_user_edit_single_capability( $current_user );
			add_filter( 'user_has_cap', array( $this, 'filter_grant_temporary_capabilities' ), 99, 3 );
			add_action( 'shutdown', array( $this, 'remove_temporary_capabilities' ) );
		}
	}

	/**
	 * Grant upload_files capability to the given current user.
	 *
	 * @param \WP_User $current_user
	 * @since 2.4
	 */
	private function grant_user_upload_capability( $current_user ) {
		if ( $current_user->has_cap( 'upload_files' ) ) {
			return;
		}
		$this->temporary_caps[] = 'upload_files';
		$current_user->add_cap( 'upload_files' );
	}

	/**
	 * Grant upload_files capability to the given current user.
	 *
	 * @param \WP_User $current_user
	 * @since 2.4
	 */
	private function grant_user_edit_single_capability(
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$current_user
	) {
		// Do nothing if there is no post to check against
		if ( ! isset( $_REQUEST['post_id'] ) ) {
			return;
		}
		// Do not check whether $current_user->has_cap( 'edit_post', $post_id )
		// because the relevant post type might not be registered yet,
		// hence firing a PHP error
		$this->temporary_caps[] = 'edit_post';
	}

	/**
	 * Grant edit_posts capability to the given current user.
	 *
	 * @param \WP_User $current_user
	 * @since 2.4
	 */
	private function grant_user_edit_capability( $current_user ) {
		if ( $current_user->has_cap( 'edit_posts' ) ) {
			return;
		}
		$this->temporary_caps[] = 'edit_posts';
		$current_user->add_cap( 'edit_posts' );
	}

	/**
	 * Grant edit_others_posts and edit_published_posts capabilities to the given current user.
	 *
	 * @param \WP_User $current_user
	 * @since 2.4
	 */
	private function grant_user_edit_full_capability( $current_user ) {
		if (
			$current_user->has_cap( 'edit_others_posts' )
			&& $current_user->has_cap( 'edit_published_posts' )
		) {
			return;
		}
		$this->temporary_caps[] = 'edit_others_posts';
		$this->temporary_caps[] = 'edit_published_posts';
		$current_user->add_cap( 'edit_published_posts' );
		$current_user->add_cap( 'edit_others_posts' );
	}

	/**
	 * Make sure that the current user gets the forced temporary capabilities
	 * inside the right AJAX call, regardless the Access settings, if any.
	 *
	 * Note that some capabilities are mapped to custom ones: take care of those too.
	 *
	 * Note that this operation might be expensive: we are filtering every time
	 * there is a check on current_user_can; however, this only happens inside
	 * the right AJAX callbacks: frontend media-related ones.
	 * Also, we have a blacklist of capabilities not to check against.
	 *
	 * @param array $allcaps
	 * @param array $caps
	 * @param array $args
	 * @return array
	 * @since 2.4
	 */
	public function filter_grant_temporary_capabilities( $allcaps, $caps, $args ) {
		if (
			empty( $this->temporary_caps )
			|| ! isset( $caps )
			|| ! is_array( $caps )
			|| empty( $caps )
		) {
			return $allcaps;
		}

		// Grant default, stored temporary caps
		foreach ( $caps as $capability_to_maybe_grant ) {
			if ( in_array( $capability_to_maybe_grant, $this->temporary_caps, true ) ) {
				$allcaps[ $capability_to_maybe_grant ] = 1;
			}
		}

		// If the caps to check are already covered, return
		$pending_caps_to_evaluate = array_diff( $caps, $this->temporary_caps );
		if ( empty( $pending_caps_to_evaluate ) ) {
			return $allcaps;
		}

		// If the pending caps belong to a blacklist of caps to skip mapping, return
		$pending_caps_to_evaluate = array_diff( $pending_caps_to_evaluate, $this->caps_to_skip_mapping );
		if ( empty( $pending_caps_to_evaluate ) ) {
			return $allcaps;
		}

		// Some caps get transformed by map_meta_cap
		// into a list of capabilities to grant, including custom
		// edit_{post_type} capabilities if the post type is defined
		// as to map custom capabilities
		$transformed_caps_to_temporary_store = array();
		foreach ( $this->temporary_caps as $temporary_cap_to_transform ) {
			$args_to_transform = $args;
			// $args is an array holding:
			// - The requested capability to transform.
			// - The ID of the current user being checked.
			// - The extra object ID to check against, if any.
			$args_to_transform[0] = $temporary_cap_to_transform;
			$transformed_caps = (
					'edit_post' !== $temporary_cap_to_transform
					|| count( $args_to_transform ) > 2
				)
				// Map the temporary capability, it might generate multiple custom ones...
				? call_user_func_array( 'map_meta_cap', $args_to_transform )
				// ... unless the temporary capability is 'edit_post' and it has no post ID to check against
				: array();
			foreach ( $transformed_caps as $transformed_cap ) {
				if (
					// The transformed cap matches the one we got requested
					in_array( $transformed_cap, $caps, true )
					// And it is not one of the caps we already granted
					&& ! in_array( $transformed_cap, $this->temporary_caps, true )
					// And it is not one of the caps we already covered in this loop
					&& ! in_array( $transformed_cap, $transformed_caps_to_temporary_store, true )
				) {
					// Store for future reference: avoid granting twice
					$transformed_caps_to_temporary_store[] = $transformed_cap;
					$allcaps[ $transformed_cap ] = 1;
				}
			}
		}

		$this->temporary_caps = array_merge( $this->temporary_caps, $transformed_caps_to_temporary_store );
		$this->temporary_caps = array_unique( $this->temporary_caps );

		return $allcaps;
	}

	/**
	 * Restore the current logged in user capabilities in case they were extended.
	 *
	 * @since 2.4
	 */
	public function remove_temporary_capabilities() {
		$current_user = wp_get_current_user();
		foreach ( $this->temporary_caps as $capability_to_remove ) {
			$current_user->remove_cap( $capability_to_remove );
		}
	}

	/**
	 * Filter the list of attachments available in a Toolset.CRED.FrontendMediaManager instance:
	 * - filter by author.
	 *
	 * @param array $query
	 * @return array
	 * @since 2.4
	 */
	public function filter_query_attachments( $query ) {
		if (
			! $this->toolset_constants->defined( 'DOING_AJAX' )
			|| ! $this->toolset_constants->constant( 'DOING_AJAX' )
		) {
			return $query;
		}

		$current_user = wp_get_current_user();

		if ( 0 > $current_user->ID ) {
			return $query;
		}

		$current_action = toolset_getpost( 'action' );

		if ( 'query-attachments' !== $current_action ) {
			return $query;
		}

		$current_origin = toolset_getnest( $_POST, array( 'query', 'toolset_media_management_origin' ) );
		// Special case for Add media button inside a Forms form: we want to filter its content, but there's no custom
		// nonce to check, it's a WP core dialog.
		if ( 'toolsetFormsAddMedia' !== $current_origin ) {
			if ( 'toolsetForms' !== $current_origin ) {
				return $query;
			}

			$custom_nonce = toolset_getnest( $_POST, array( 'query', 'toolset_media_management_nonce' ) );
			if ( ! wp_verify_nonce( $custom_nonce, 'toolset_media_field_' . $current_user->ID ) ) {
				return $query;
			}
		}

		$include_author_filter = toolset_getnest( $_POST, array( 'query', 'toolset_media_management_filter', 'author' ), false );
		$form_id = toolset_getnest( $_POST, array( 'query', 'toolset_media_management_form_id' ), 0 );

		if (
			$include_author_filter
			|| $this->should_query_only_own_attachments( $form_id )
		) {
			$query['author'] = $current_user->ID;
		}

		return $query;
	}

	/**
	 * Check whether the current user should only query own attachments.
	 *
	 * @param int $form_id
	 * @return bool
	 * @since 2.4
	 */
	private function should_query_only_own_attachments( $form_id ) {
		return ( ! apply_filters( 'toolset_forms_current_user_can_use_any_attachment', false, $form_id ) );
	}

	/**
	 * Apply validation checks to each media files upload.
	 *
	 * @param array $file
	 * @return array
	 * @since 2.4
	 */
	public function validate_native_uploads( $file ) {
		if ( 'toolsetForms' !== toolset_getpost( 'toolsetOrigin' ) ) {
			return $file;
		}

		$form_id = toolset_getpost( 'toolsetFormsFormId' );
		$form = get_post( $form_id );
		$form_type = $form->post_type;
		$form_slug = $form->post_name;
		$field_name = toolset_getpost( 'toolsetFormsMetaKey' );
		$field_type = toolset_getpost( 'toolsetFormsMetaType' );

		if ( CRED_USER_FORMS_CUSTOM_POST_NAME === $form_type ) {
			$post_id = -1;
			$post_type = 'user';
		} else {
			$post_id = toolset_getpost( 'toolsetFormsPostId' );
			$post_type = get_post_type( $post_id );
		}

		if ( ! $this->validate_uploaded_file_by_type( $file, $field_type ) ) {
			$file['error'] = $this->media_model->get_upload_validation_error_message( $field_type );
		}

		$form_data = array(
			'id' => $form_id,
			'post_type' => $post_type,
			'form_type' => $form_type,
		);

		$fields = array(
			$field_name => array(
				'field_data' => $file,
			),
		);

		$errors = array();

		list ( $fields, $errors ) = apply_filters( 'cred_form_ajax_upload_validate_' . $form_slug, array( $fields, $errors ), $form_data );
		list ( $fields, $errors ) = apply_filters( 'cred_form_ajax_upload_validate_' . $form_id, array( $fields, $errors ), $form_data );
		list ( $fields, $errors ) = apply_filters( 'cred_form_ajax_upload_validate', array( $fields, $errors ), $form_data );

		if ( ! empty( $errors ) ) {
			// We can set only one field error at a time,
			// so we override whatever error was set before,
			// including the validation per field type.
			foreach ( $errors as $field_name => $error_text ) {
				$file['error'] = $error_text;
			}
		}

		return $file;
	}

	/**
	 * Validate an uploaded file by its mime type, per field type.
	 *
	 * @param array $file
	 * @param string $field_type
	 * @return bool
	 * @since 2.4
	 */
	private function validate_uploaded_file_by_type( $file, $field_type ) {
		$uploaded_file_mime_type = toolset_getarr( $file, 'type' );
		$uploaded_file_tmp_name = toolset_getarr( $file, 'tmp_name' );
		$uploaded_file_name = toolset_getarr( $file, 'name' );

		if (
			empty( $uploaded_file_mime_type )
			|| empty( $uploaded_file_tmp_name )
			|| empty( $uploaded_file_name )
		) {
			return false;
		}

		$supported_mime_types = $this->media_model->get_valid_mime_types( $field_type );

		if ( in_array( $uploaded_file_mime_type, $supported_mime_types, true ) ) {
			return true;
		}

		// If the file type is not among the allowed mime types,
		// there are still chances it is a valid file:
		// for example, application/x-zip-compressed mapps to application/zip
		$uploaded_filetype = wp_check_filetype_and_ext( $uploaded_file_tmp_name, $uploaded_file_name );
		$uploaded_file_real_mime_type = toolset_getarr( $uploaded_filetype, 'type', false );

		if (
			false !== $uploaded_file_real_mime_type
			&& in_array( $uploaded_file_real_mime_type, $supported_mime_types, true )
		) {
			return true;
		}

		return false;
	}

}
