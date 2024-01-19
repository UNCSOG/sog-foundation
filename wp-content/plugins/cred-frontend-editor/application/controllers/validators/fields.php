<?php

class CRED_Validator_Fields extends CRED_Validator_Base implements ICRED_Validator {

	public function validate() {
		$form = $this->_formData;
		$cred_form_rendering = $this->_zebraForm;
		$formHelper = $this->_formHelper;
		$_fields = $form->getFields();
		$form_id = $form->getForm()->ID;
		$form_slug = $form->getForm()->post_name;
		$form_type = $_fields['form_settings']->form['type'];
		$post_type = $_fields['form_settings']->post['post_type'];
		$form_post_type = $form->getForm()->post_type;
		$fields = $formHelper->get_form_field_values();
		$cred_form_rendering->set_submitted_values( $fields );

		$current_form = array(
			'id' => $form_id,
			'post_type' => $post_type,
			'form_type' => $form_type,
		);


		$form_args = array(
			'form_id' => $form_id,
			'form_slug' => $form_slug,
			'form_post_type' => $form_post_type,
			'fields' => $fields,
			'current_form' => $current_form,
		);

		$class_name_prefix = ( $form_post_type == CRED_USER_FORMS_CUSTOM_POST_NAME ) ? "CRED_User_Form_" : "CRED_Post_Form_";
		$class = "{$class_name_prefix}Custom_Validation_Error_Message_Handler";
		if ( class_exists( $class ) ) {
			$class_object = new $class( $cred_form_rendering, $form, $form_args );

			return $class_object->start_custom_validation_error_messages();
		} else {
			throw new Exception( "Expected Class $class" );
		}
	}
}
