<?php

/**
 * Abstract Class that handles the setting error message on CRED_Form_Rendering Custom
 *
 * @since 1.9.3
 */
abstract class CRED_Base_Custom_Validation_Error_Message_Handler {

	protected $cred_form_rendering;
	protected $form_data;
	protected $form_args;

	/**
	 * CRED_Base_Custom_Validation_Error_Message_Handler constructor.
	 *
	 * @param CRED_Form_Rendering $cred_form_rendering
	 * @param CRED_Form_Data $form_data
	 * @param array $form_args array(
	 * 'form_id' => $form_id,
	 * 'form_slug' => $form_slug,
	 * 'form_post_type' => $form_post_type, {cred-form|cred-user-form}
	 * 'fields' => $fields,
	 * 'current_form' => $current_form,
	 * );
	 */
	public function __construct( CRED_Form_Rendering $cred_form_rendering, CRED_Form_Data $form_data, $form_args ) {
		$this->cred_form_rendering = $cred_form_rendering;
		$this->form_data = $form_data;
		$this->form_args = $form_args;
	}

	/**
	 * @return bool
	 */
	public function start_custom_validation_error_messages() {
		$form_id = $this->form_args['form_id'];
		$form_slug = $this->form_args['form_slug'];
		$fields = $this->form_args['fields'];
		$form_post_type = $this->form_args['form_post_type'];
		$current_form = $this->form_args['current_form'];

		$errors = array();

		list( $fields, $errors ) = apply_filters( 'cred_form_validate_form_' . $form_slug, array( $fields, $errors, ), $current_form );
		list( $fields, $errors ) = apply_filters( 'cred_form_validate_' . $form_id, array( $fields, $errors, ), $current_form );
		list( $fields, $errors ) = apply_filters( 'cred_form_validate', array( $fields, $errors ), $current_form );

		return ( empty( $errors ) ) ? true : $this->handle_custom_validation_errors_messages( $errors );
	}

	/**
	 * @param array $errors array($field_name => $error_text)
	 *
	 * @return bool
	 */
	abstract function handle_custom_validation_errors_messages( $errors );

	/**
	 * Method that search human readable field name by default field slug
	 *
	 * @param string $field_slug
	 *
	 * @return string
	 */
	protected function get_field_name( $field_slug ) {
		return toolset_getnest( CRED_StaticClass::$out, array( 'form_fields', $field_slug, 'label' ), $field_slug );
	}

}