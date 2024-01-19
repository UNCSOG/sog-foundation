<?php

/**
 * Frontend form AJAX submission callback.
 *
 * @since 2.4
 */
class CRED_Ajax_Handler_Submit_Form extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Process the AJAX callback.
	 *
	 * @param array $arguments
	 * @since 2.8
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => CRED_Ajax::CALLBACK_SUBMIT_FORM,
				'parameter_source' => 'post',
				'is_public' => true,
			)
		);

		$form_id = false;
		$post_id = false;
		$form_count = 1;
		$preview = false;
		$this->try_to_update_by_post( $form_id, $post_id, $form_count, $preview );

		$this->maybe_set_current_lang();
		$this->maybe_set_current_get();
		$this->maybe_set_current_wpv_page();

		// Set the right frontend flow index for this form
		$form = get_post( $form_id );
		do_action(
			'toolset_forms_frontend_flow_form_start',
			$form,
			array(
				'form' => $form_id,
				'post' => $post_id,
			)
		);
		do_action( 'toolset_forms_frontend_flow_set_form_index', $form_id, $form_count );

		$response_data = CRED_Form_Builder::initialize()->get_form( $form_id, $post_id, $form_count, $preview );

		$this->ajax_finish( $response_data, true );
	}

	/**
	 * Try to set the right form and post from the POSTed data, when processing an AJAX form.
	 * Also, try to set the right current post for environmental shortcodes.
	 *
	 * @param int|bool $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 * @since 2.8
	 */
	private function try_to_update_by_post( &$form_id, &$post_id, &$form_count, &$preview ) {
		if (
			array_key_exists( CRED_StaticClass::PREFIX . 'form_id', $_POST )
			&& array_key_exists( CRED_StaticClass::PREFIX . 'form_count', $_POST )
		) {
			$form_id = intval( toolset_getpost( CRED_StaticClass::PREFIX . 'form_id' ) );
			$form_count = intval( toolset_getpost( CRED_StaticClass::PREFIX . 'form_count' ) );
			$post_id = ( array_key_exists( CRED_StaticClass::PREFIX . 'post_id', $_POST ) ) ? intval( toolset_getpost( CRED_StaticClass::PREFIX . 'post_id' ) ) : false;
			$preview = ( array_key_exists( CRED_StaticClass::PREFIX . 'form_preview_content', $_POST ) ) ? true : false;

			$environmental_post_id = ( isset( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) ) ? intval( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) : 0;
			global $post;
			$post = get_post( $environmental_post_id );
		}
	}

	/**
	 * Maybe set the current language as set when submitting the form.
	 *
	 * @since 2.6
	 */
	private function maybe_set_current_lang() {
		$lang = toolset_getpost( 'lang' );
		do_action( 'wpml_switch_language', $lang );
	}

	/**
	 * Maybe set the current GET parameters as set when submitting the form.
	 *
	 * @since 2.7
	 */
	private function maybe_set_current_get() {
		$currentGet = toolset_getpost( 'currentGet' );
		if ( ! empty( $currentGet ) ) {
			$_GET = $currentGet;
		}
	}

	/**
	 * Maybe set the current Views pagination parameter as set when submitting the form.
	 *
	 * @since 2.7
	 */
	private function maybe_set_current_wpv_page() {
		$currentWpvPage = toolset_getpost( 'wpvPage' );
		if ( ! empty( $currentWpvPage ) ) {
			$_GET['wpv_paged'] = $currentWpvPage;
		}
	}

}
