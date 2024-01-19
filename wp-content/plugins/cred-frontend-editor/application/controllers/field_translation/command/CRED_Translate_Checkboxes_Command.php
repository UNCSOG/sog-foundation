<?php

class CRED_Translate_Checkboxes_Command extends CRED_Translate_Options_Command {

	protected $titles = array();

	public function execute() {
		$this->set_field_configuration_as_array();
		$this->set_field_configuration_value();
		$this->set_field_value_and_attributes();

		$this->field_attributes['default'] = $this->field_attributes;
		$this->field_attributes['actual_titles'] = $this->titles;

		if ( isset( CRED_StaticClass::$out['field_values_map'][ $this->field['slug'] ] ) ) {
			$this->field_attributes['actual_values'] = CRED_StaticClass::$out['field_values_map'][ $this->field['slug'] ];
		}

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}

	private function set_field_configuration_as_array() {
		if ( ! empty( $this->field_configuration )
			&& ! is_array( $this->field_configuration )
		) {
			$this->field_configuration = array( $this->field_configuration );
		}
	}

	protected function set_field_configuration_value() {
		$save_empty = isset( $this->field['data']['save_empty'] ) ? $this->field['data']['save_empty'] : false;

		if ( isset( $this->field_configuration )
			&& ! empty( $this->field_configuration )
		) {
			if ( ! is_array( $this->field_configuration ) ) {
				if ( isset( $this->field['data']['options'] )
					&& ! empty( $this->field['data']['options'] )
				) {
					foreach ( $this->field['data']['options'] as $option_key => $option_value ) {
						if ( $option_value['set_value'] == $this->field_configuration ) {
							$this->field_configuration = array( $option_key => $this->field_configuration );
						}
					}
				}
			} else {
				if ( count( array_filter( array_keys( $this->field_configuration ), 'is_string' ) ) > 0 ) {
					$new_data_value = array();
					if ( isset( $this->field['data']['options'] )
						&& ! empty( $this->field['data']['options'] )
					) {
						foreach ( $this->field['data']['options'] as $option_key => $option_value ) {
							if ( in_array( $option_value['set_value'], $this->field_configuration ) ) {
								$new_data_value[ $option_key ] = $option_value['set_value'];
							}
						}
					}
					$this->field_configuration = $new_data_value;
					unset( $new_data_value );
				}
			}

			foreach ( $this->field_configuration as $config_key => $config_value ) {
				if ( $save_empty
					|| $this->field['cred_generic'] == 1
				) {
					$this->field_value[ $config_key ] = $config_value;
				} else {
					$this->field_value[ $config_key ] = 1;
				}
			}
		}
	}

	protected function set_field_value_and_attributes() {
		$this->field_value = toolset_ensarr($this->field_value);

		if ( isset( $this->field['data']['options'] )
			&& ! empty( $this->field['data']['options'] )
		) {
			foreach ( $this->field['data']['options'] as $key => $option ) {
				if ( is_admin() ) {
					//register strings on form save
					cred_translate_register_string( $this->cred_form_prefix, $this->field['slug'] . " " . $option['title'], $option['title'], false );
				}
				$option = $this->_cred_translate_option( $option, $key, $this->form, $this->field );

				$index = $key;
				$this->titles[ $index ] = $option['title'];
				if ( isset( $this->field_configuration )
					&& ! empty( $this->field_configuration )
					&& isset( $this->field_configuration[ $index ] )
				) {
					$this->field_value[ $index ] = $this->field_configuration[ $index ];
				} else {
					$this->field_value[ $index ] = 0;
				}
				// For Types checkboxes fields, $this->field_configuration holds:
				// - an array of selected values when editing a post; empty if none is selected
				// - an empty string when creating a post
				if ( isset( $option['checked'] )
					&& $option['checked']
					&& ! is_array( $this->field_configuration )
				) {
					$this->field_attributes[] = $index;
				} elseif ( is_array( $this->field_configuration )
					&& isset( $this->field_configuration[ $index ] )
				) {
					if (
					! ( isset( $this->field['data']['save_empty'] )
						&& 'yes' == $this->field['data']['save_empty']
						&& ( 0 === $this->field_configuration[ $index ]
							|| '0' === $this->field_configuration[ $index ] ) )
					) {
						$this->field_attributes[] = $index;
					}
				}
			}
		}
	}
}