<?php

/**
 * Class CRED_Form_Builder_Base
 */
abstract class CRED_Form_Builder_Base {

	const FORM_ID_PLACEHOLDER = '%%FORM_ID%%';

	/**
	 * CRED_Form_Builder_Base constructor.
	 */
	public function __construct() {
		// load front end form assets
		add_action( 'wp_footer', array( 'CRED_Asset_Manager', 'unload_frontend_assets' ) );
	}


	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count Related to the submit count form or just 1
	 * @param bool $preview
	 *
	 * @return bool
	 */
	public function get_form( $form_id, $post_id = false, $form_count = 1, $preview = false ) {
		global $post;
		CRED_StaticClass::$_cred_container_id = ( isset( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) ) ? intval( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) : ( isset( $post ) ? $post->ID : "" );

		//Security Check
		if ( isset( CRED_StaticClass::$_cred_container_id ) && ! empty( CRED_StaticClass::$_cred_container_id ) ) {
			if ( ! is_numeric( CRED_StaticClass::$_cred_container_id ) ) {
				wp_die( 'Invalid data' );
			}
		}

		// Restore the list of fields in the current form.
		CRED_StaticClass::$out['current_form_fields'] = array();
		$form = $this->get_cred_form_object( $form_id, $post_id, $form_count, $preview );
		$type_form = $form->get_type_form();
		$output = $form->print_form();

		if ( is_wp_error( $output ) ) {
			$error_message = $output->get_error_message();

			return $error_message;
		}

		$html_form_id = get_cred_html_form_id( $type_form, $form_id, $form_count );

		$output = $this->replace_form_id_placeholders( $output, $html_form_id );

		/**
		 * cred_after_rendering_form
		 *
		 *  This action is fired after each Toolset Form rendering just before its output.
		 *
		 * @param string $form_id ID of the current cred form
		 * @param string $html_form_id ID of the current cred form
		 * @param int $form_id Toolset Form id
		 * @param string $type_form Post type of the form
		 * @param int $form_count Number of forms rendered so far
		 *
		 * @since 1.9.3
		 */
		do_action( 'cred_after_rendering_form', $form_id, $html_form_id, $form_id, $type_form, $form_count );

		/**
		 * cred_after_rendering_form_{$form_id}
		 *
		 *  This action is fired after specific Toolset Form $form_id rendering just before its output.
		 *
		 * @param string $html_form_id ID of the current cred form
		 * @param int $form_id Toolset Form id
		 * @param string $type_form Post type of the form
		 * @param int $form_count Number of forms rendered so far
		 *
		 * @since 1.9
		 */
		do_action( 'cred_after_rendering_form_' . $form_id, $html_form_id, $form_id, $type_form, $form_count );

		return $output;
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return CRED_Form_Post|CRED_Form_User
	 */
	protected function get_cred_form_object( $form_id, $post_id, $form_count, $preview ) {
		$type_form = get_post_type( $form_id );
		switch ( $type_form ) {
			case CRED_USER_FORMS_CUSTOM_POST_NAME:
				$form = $this->get_user_form( $form_id, $post_id, $form_count, $preview );
				break;
			default:
			case CRED_FORMS_CUSTOM_POST_NAME:
				$form = $this->get_post_form( $form_id, $post_id, $form_count, $preview );
				break;

		}

		CRED_StaticClass::initVars();

		return $form;
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return CRED_Form_User
	 */
	private function get_user_form( $form_id, $post_id, $form_count, $preview ) {
		$form = new CRED_Form_User( $form_id, $post_id, $form_count, $preview );

		return $form;
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return CRED_Form_Post
	 */
	private function get_post_form( $form_id, $post_id, $form_count, $preview ) {
		$form = new CRED_Form_Post( $form_id, $post_id, $form_count, $preview );

		return $form;
	}

	/**
	 * Replaces placeholders by forms ID
	 *
	 * Labels must point to proper inputs for a11y, but how Forms is implemented, they can point directly, so a placeholder has been added %%FORM_ID%%
	 */
	private function replace_form_id_placeholders( $html, $id ) {
		return str_replace( self::FORM_ID_PLACEHOLDER, $id, $html );
	}

}
