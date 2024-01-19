<?php

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\FormsTranslation;

/**
 * Forms translation controller using legacy WPML ST.
 *
 * @since 2.6
 */
class Legacy extends Base {

	const SHORTCODE_NAME = 'wpml-string';

	public function initialize() {

	}

	protected function register_string( $title, $value, $context, $id_in_group = '', $type = '', $existing_translation = null ) {
		do_action(
			'wpml_register_single_string',
			$context,
			$title,
			$value
		);
	}

	private function get_content_prefix( \WP_Post $form ) {
		if ( \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE === $form->post_type ) {
			return 'cred-user-form-';
		}

		return 'cred-form-';
	}

	protected function get_form_context( \WP_Post $form ) {
		return $this->get_content_prefix( $form ) . $form->post_title . '-' . $form->ID;
	}

	protected function get_form_context_alt( \WP_Post $form ) {
		return $this->get_content_prefix( $form ) . $form->post_name;
	}

	protected function register_strings( \WP_Post $form, $form_data ) {
		$context = $this->get_form_context( $form );
		$contex_alt = $this->get_form_context_alt( $form );

		// Title.
		$this->register_string( 'Form Title: ' . $form->post_title, $form->post_title, $context );

		// Form message.
		if ( ! empty( $form_data['message'] ) ) {
			$this->register_string( 'Display Message: ' . $form->post_title, $form_data['message'], $context );
		}

		// Legacy hook after registering a form.
		do_action( 'cred_localize_form', $form_data );
	}

}
