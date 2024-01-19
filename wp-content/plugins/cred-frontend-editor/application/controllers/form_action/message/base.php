<?php

namespace OTGS\Toolset\CRED\Controller\FormAction\Message;

/**
 * Forms action as message base controller.
 *
 * @since 2.1.2
 */
class Base {

	/**
	 * Get the message to display after submitting a form.
	 *
	 * @param \CRED_Form_Data $form
	 *
	 * @return string
	*
	* @since 2.1.2
	 */
	public function get_action_message( \CRED_Form_Data $form_data ) {
		$form_fields = $form_data->getFields();
		$form_object = $form_data->getForm();

		$rendered_message = cred_translate(
			'Display Message: ' . $form_object->post_title,
			$form_fields['form_settings']->form['action_message'],
			'cred-form-' . $form_object->post_title . '-' . $form_object->ID
		);

		$this->restore_form_shortcodes();

		// Packages translation or other hook
		$rendered_message = apply_filters( 'cred_translate_action_message', $rendered_message, 'display-message', $form_object->ID );

		// Apply filters and set context
		$rendered_message = $this->apply_content_to_action_message(
			$rendered_message
		);

		$success_message_id = sanitize_text_field( toolset_getget( '_success_message' ) );

		$message = sprintf( '<div id="cred_form_%s">%s</div>', esc_attr( $success_message_id ), $rendered_message );
		return $message;
	}

	/**
	 * Restore the Forms shortcodes, removed earlier to prevent recursive loops.
	 *
	 * @since 2.4
	 * @todo Include here a security check to avoid trying to print the message inside itself.
	 */
	protected function restore_form_shortcodes() {
		add_shortcode( 'cred-form', array( 'CRED_Helper', 'credFormShortcode' ) );
		add_shortcode( 'cred_form', array( 'CRED_Helper', 'credFormShortcode' ) );
	}

	/**
	 * Apply the right context to messages displayed after submitting post forms.
	 *
	 * @param string $message
	 * @return string
	 * @since 2.1.2
	 */
	protected function apply_content_to_action_message( $message ) {
		return $message;
	}


	/**
	 * Gets messages by ID
	 *
	 * @param CRED_Form_Data $form_data Form model
	 * @param string $key Message key
	 * @return strung
	 */
	public function get_message_by_id( \CRED_Form_Data $form_data, $key ) {
		$fields = $form_data->getFields();
		$messages = $fields['extra']->messages;
		// It seems that this one needs to be included too
		$messages['cred_message_no_recaptcha_keys'] = __( 'no recaptcha keys found', 'wp-cred' );

		$extra_messsage_id = 'cred_message_' . $key;
		if ( ! isset( $messages[ $extra_messsage_id ] ) ) {
			return '';
		}
		// Messages need to be translated
		// - First translate it using WPML ST
		// - If it wasn't translated using WPML then use TM packages

		$form_id = $form_data->getForm()->ID;
		$translated_message = cred_translate(
			\CRED_Form_Builder_Helper::MSG_PREFIX . $extra_messsage_id,
			$messages[$extra_messsage_id],
			'cred-form-' . $form_data->getForm()->post_title . '-' . $form_id
		);

		if ( $messages[$extra_messsage_id] === $translated_message ) {
			$translated_message = apply_filters( 'cred_translate_action_message', $translated_message, 'message-' . $extra_messsage_id, $form_id );
		}

		return $translated_message;
	}
}
