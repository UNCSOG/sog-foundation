<?php

class CRED_Translate_Date_Command extends CRED_Translate_Field_Command_Base {

	public function execute() {
		if ( ! function_exists( 'adodb_mktime' ) ) {
			require_once WPTOOLSET_FORMS_ABSPATH . '/lib/adodb-time.inc.php';
		}

		$value = array();
		
		$format = get_option( 'date_format', '' );
		if ( empty( $format ) ) {
			$cred_form_rendering = $this->cred_translate_field_factory->_formBuilder->get_form_rendering();
			$format = $cred_form_rendering->getDateFormat();
			$format .= " h:i:s";
		}

		$this->field_attributes = array_merge( $this->field_attributes, array(
			'format' => $format,
			'readonly_element' => false,
			'repetitive' => isset( $this->field['data']['repetitive'] ) ? $this->field['data']['repetitive'] : 0,

		) );

		if (
			isset( $this->field_configuration )
			&& ! empty( $this->field_configuration )
		) {
			if ( is_array( $this->field_configuration ) ) {
				foreach ( $this->field_configuration as $dv ) {
					if ( isset( $dv['datepicker'] ) ) {
						$value[] = array( 'timestamp' => $dv['datepicker'] );
					} else {
						$value[] = array( 'timestamp' => $dv );
					}
				}
			} else {
				$value['timestamp'] = $this->field_configuration;
			}
		}
		
		$this->field_value = $value;

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}
}