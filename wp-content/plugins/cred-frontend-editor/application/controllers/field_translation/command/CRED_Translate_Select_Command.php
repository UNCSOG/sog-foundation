<?php

class CRED_Translate_Select_Command extends CRED_Translate_Options_Command {

	protected $titles;
	protected $default_value;

	public function execute() {
		$this->set_field_value_and_attributes();

		if ( isset( CRED_StaticClass::$out['field_values_map'][ $this->field['slug'] ] ) ) {
			$this->field_attributes['actual_options'] = CRED_StaticClass::$out['field_values_map'][ $this->field['slug'] ];
		}

		if ( isset( $this->field_attributes['select_text'] )
			&& ! empty( $this->field_attributes['select_text'] )
		) {
			$this->field_attributes['select_text'] = cred_translate( "Default Label: {$this->field_attributes['select_text']}", $this->field_attributes['select_text'], $this->form->getForm()->post_type . "-" . $this->form->getForm()->post_title . "-" . $this->form->getForm()->ID );
		}

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}
	
	protected function set_field_value_and_attributes() {
		if ( $this->field['type'] == 'multiselect' ) {
			$this->field_attributes['multiple'] = 'multiple';
		}

		$this->field_attributes['options'] = array();

		if ( isset( $this->field['data']['options'] )
			&& ! empty( $this->field['data']['options'] )
		) {
			foreach ( $this->field['data']['options'] as $key => $option ) {
				$index = $key;

				if ( 'default' === $key
					&& $option != 'no-default'
				) {
					$this->default_value[] = $option;
				} else {
					if ( is_admin() ) {
						if ( isset( $option['title'] ) ) {
							cred_translate_register_string( $this->cred_form_prefix, $this->field['slug'] . " " . $option['title'], $option['title'], false );
						}
					}
					if ( isset( $option['title'] ) ) {
						$option = $this->_cred_translate_option( $option, $key, $this->form, $this->field );
						$this->field_attributes['options'][ $index ] = $option['title'];

						$is_field_configuration_set = isset( $this->field_configuration ) && ( ! empty( $this->field_configuration ) || is_numeric( $this->field_configuration ) );
						$is_field_checked = ( $is_field_configuration_set && $this->field_configuration == $option['value'] );
						$is_a_field_option_checked = ( $is_field_configuration_set && ( is_array( $this->field_configuration ) && ( array_key_exists( $option['value'], $this->field_configuration ) || in_array( $option['value'], $this->field_configuration ) ) ) );

						if ( $is_field_checked
							|| $is_a_field_option_checked
						) {
							if ( 'select' == $this->field['type'] ) {
								$this->titles[] = $key;
								$this->field_value = $option['value'];
							} else {
								$this->field_value = $this->field_configuration;
							}
						}

						if ( isset( $option['dummy'] ) && $option['dummy'] ) {
							$this->field_attributes['dummy'] = $key;
						}
					}
				}
			}
		}

		if ( $this->field['type'] == 'multiselect' ) {
			if ( empty( $this->field_value )
				&& ! empty( $this->default_value )
			) {
				$this->field_value = $this->default_value;
			}
		} else {
			if ( empty( $this->titles )
				&& ! empty( $this->default_value[0] )
			) {
				$this->titles = isset( $this->field['data']['options'][ $this->default_value[0] ]['value'] ) ? $this->field['data']['options'][ $this->default_value[0] ]['value'] : "";
			}
			$this->field_attributes['actual_value'] = isset( $this->field_configuration ) && ( ! empty( $this->field_configuration ) || is_numeric( $this->field_configuration ) ) ? $this->field_configuration : $this->titles;
		}
	}
}