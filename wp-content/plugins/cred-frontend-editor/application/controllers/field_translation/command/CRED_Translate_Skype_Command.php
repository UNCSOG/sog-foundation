<?php

class CRED_Translate_Skype_Command extends CRED_Translate_Field_Command_Base {

	public function execute() {
		$this->field_value = $this->get_value_by_configuration();
		$this->field_attributes = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'edit_skype_text' => $this->cred_translate_field_factory->_formHelper->getLocalisedMessage(
				'edit_skype_button'
			),
			'_nonce' => wp_create_nonce( 'insert_skype_button' ),
		);

		return new CRED_Field_Translation_Result(
			$this->field_configuration, $this->field_type, $this->field_name,
			$this->field_value, $this->field_attributes, $this->field
		);
	}


	/**
	 * Elaborate field_configuration in order to retrieve the correct field_value
	 *
	 * @return array
	 */
	public function get_value_by_configuration() {
		//Init empty field_configuration
		$field_configuration = toolset_ensarr( $this->field_configuration );

		//Check if field is repetitive
		if ( isset( $this->field['data']['repetitive'] ) ) {
			if ( $this->field['data']['repetitive'] == 0
				&& isset( $field_configuration[0] )
			) {
				$field_configuration = $field_configuration[0];
			}

			if ( $this->field['data']['repetitive'] == 1
				&& ! isset( $field_configuration[0] )
			) {
				$field_configuration = array( $field_configuration );
			}
		} else {
			//Create the default field configuration
			if ( empty( $field_configuration ) ) {
				$field_configuration = array( 'skypename' => '', 'style' => '' );
			}
		}

		return $field_configuration;
	}
}
