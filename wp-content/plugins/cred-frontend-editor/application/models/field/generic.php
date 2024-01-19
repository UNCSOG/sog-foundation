<?php

class CRED_Generic_Field extends CRED_Generic_Field_Abstract {

	/**
	 * CRED_Generic_Field constructor.
	 *
	 * @param array $atts
	 * @param string $content
	 * @param CRED_Form_Rendering $cred_form_rendering
	 * @param CRED_Helper $formHelper
	 * @param CRED_Form_Data $formData
	 * @param CRED_Translate_Field_Factory $translate_field_factory
	 */
    public function __construct($atts, $content, $cred_form_rendering, $formHelper, $formData, $translate_field_factory) {
        parent::__construct($atts, $content, $cred_form_rendering, $formHelper, $formData, $translate_field_factory);
    }

    public function get_field() {
        $filtered_attributes = shortcode_atts(array(
            'field' => '',
            'type' => '',
            'class' => '',
            'use_select2' => null,
            'placeholder' => null,
			'urlparam' => '',
			'preview' => '',
			'previewsize' => '',
		), $this->_atts);

        $content = $this->_content;
        $content = ( null === $content ) ? '{}': $content;

	    if ( empty( $filtered_attributes['field'] )
		    || empty( $filtered_attributes['type'] )
		    || null == $content
		    || empty( $content ) ) {
		    return ''; // ignore
	    }

        $field_data = json_decode(preg_replace('/[\r\n]/', '', $content), true); // remove NL (crlf) to prevent json_decode from failing
        // only for php >= 5.3.0
	    if (
		    ( function_exists( 'json_last_error' )
			    && json_last_error() != JSON_ERROR_NONE )
		    || empty( $field_data ) /* probably JSON decode error */
	    ) {
		    return ''; //ignore not valid json
        }

        $field_data_defaults = array(
            'required' => 0,
            'validate_format' => 0,
            'checked' => 0,
            'default' => '',
            'label' => '',
            'persist' => 0,
            //'generic_type' => '',// No need to add this as a default since we check whether isset or not
            'options' => array()// Each entry is an array( 'value' => '', 'label' => '' ), or just one entry with the shortcode that provides this
        );
		$field_data = wp_parse_args( $field_data, $field_data_defaults );

		// Since Forms 2.6, selected values can be defined within the options.
		$field_data = $this->maybe_parse_defaults_from_options( $field_data );

        $formHelper = $this->_formHelper;

        $field = array(
            'id' => $filtered_attributes['field'],
            'cred_generic' => true,
            'slug' => $filtered_attributes['field'],
            'type' => $filtered_attributes['type'],
            'name' => $filtered_attributes['field'],
            'data' => array(
                'repetitive' => 0,
                'validate' => array(
                    'required' => array(
                        'active' => $field_data['required'],
                        'value' => $field_data['required'],
                        'message' => $formHelper->getLocalisedMessage('field_required')
                    )
                ),
                'validate_format' => $field_data['validate_format'],
                'persist' => isset($field_data['persist']) ? $field_data['persist'] : 0
            )
        );

        $default = $field_data['default'];
        $class = ( isset($filtered_attributes['class']) ) ? $filtered_attributes['class'] : '';

        switch ($filtered_attributes['type']) {
            case 'checkbox':
                $field['label'] = isset($field_data['label']) ? $field_data['label'] : '';
                $field['data']['set_value'] = $field_data['default'];
                if ($field_data['checked'] != 1) {
	                $default = null;
                } else {
                	$field['data']['default_checked'] = 1;
                }

                break;
            case 'checkboxes':
				$field['data']['options'] = array();
				// Make sure that the defaults are always an array!!
				$field_data['default'] = ( '' === $field_data['default'] )
					? array()
					: ( is_array( $field_data['default'] ) ? $field_data['default'] : array( $field_data['default'] ) );

                foreach ($field_data['options'] as $ii => $option) {
                    if (
                        ! is_array( $option )
                        || ! array_key_exists( 'value', $option )
                        || ! array_key_exists( 'label', $option )
                    ) {
                        continue;
                    }
                    $option_id = $option['value'];
                    $field['data']['options'][$option_id] = array(
                        'title' => $option['label'],
                        'set_value' => $option['value']
                    );
                    if ( in_array( $option['value'], $field_data['default'] ) ) {
                        $field['data']['options'][$option_id]['checked'] = true;
                    }
                    /**
                     * check post data, maybe this form fail validation
                     */
	                if (
		                ! empty( $_POST )
		                && array_key_exists( $field['id'], $_POST )
		                && is_array( $_POST[ $field['id'] ] )
		                && in_array( $option['value'], $_POST[ $field['id'] ] )
	                ) {
                        $field['data']['options'][$option_id]['checked'] = true;
                    }
                }
                $default = null;
                break;
            case 'date':
                $field['data']['validate']['date'] = array(
                    'active' => $field_data['validate_format'],
                    'format' => 'mdy',
                    'message' => $formHelper->getLocalisedMessage('enter_valid_date')
                );
                $field['data']['date_and_time'] = isset($field_data['date_and_time']) ? $field_data['date_and_time'] : '';
                break;
            case 'hidden':
                $field['data']['validate']['hidden'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('values_do_not_match')
                );
                break;
            case 'radio':
            case 'select':
				$field['data']['options'] = array();
				if ( '' === $field_data['default'] ) {
					$field_data['default'] = array();
				} elseif ( ! is_array( $field_data['default'] ) ) {
					$field_data['default'] = array( $field_data['default'] );
				}
                $default_option = 'no-default';
                foreach ($field_data['options'] as $ii => $option) {
                    if (
                        ! is_array( $option )
                        || ! array_key_exists( 'value', $option )
                        || ! array_key_exists( 'label', $option )
                    ) {
                        continue;
                    }
                    $option_id = $option['value'];

                    $field['data']['options'][$option_id] = array(
                        'title' => $option['label'],
                        'value' => $option['value'],
                        'display_value' => $option['value']
                    );
	                if ( ! empty( $field_data['default'] )
		                && $field_data['default'][0] == $option['value'] ) {
		                $default_option = $option_id;
	                }
                }
                $field['data']['options']['default'] = $default_option;
                $default = null;
                break;
            case 'multiselect':
				$field['data']['options'] = array();
				// Make sure that the defaults are always an array!!
				$field_data['default'] = ( '' === $field_data['default'] )
					? array()
					: ( is_array( $field_data['default'] ) ? $field_data['default'] : array( $field_data['default'] ) );
                $default_option = array();
                foreach ($field_data['options'] as $ii => $option) {
                    if (
                        ! is_array( $option )
                        || ! array_key_exists( 'value', $option )
                        || ! array_key_exists( 'label', $option )
                    ) {
                        continue;
                    }
                    $option_id = $option['value'];
                    $field['data']['options'][$option_id] = array(
                        'title' => $option['label'],
                        'value' => $option['value'],
                        'display_value' => $option['value']
                    );
	                if ( ! empty( $field_data['default'] )
		                && in_array( $option['value'], $field_data['default'] ) ) {
		                $default_option[] = $option_id;
	                }
                }
                $field['data']['options']['default'] = $default_option;
                $field['data']['is_multiselect'] = 1;
                $default = null;

                break;
            case 'email':
                $field['data']['validate']['email'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('enter_valid_email')
                );
                break;
            case 'numeric':
                $field['data']['validate']['number'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('enter_valid_number')
                );
                break;
            case 'integer':
                $field['data']['validate']['integer'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('enter_valid_number')
                );
                break;
            case 'embed':
            case 'url':
                $field['data']['validate']['url'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('enter_valid_url')
                );
                break;
	        case 'colorpicker':
                $field['data']['validate']['hexadecimal'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('enter_valid_colorpicker')
                );
                break;
            default:
                $default = $field_data['default'];
                break;
        }

        $name = $field['slug'];
        if ($filtered_attributes['type'] == 'image' || $filtered_attributes['type'] == 'file') {
	        if ( isset( $field_data['max_width'] ) && is_numeric( $field_data['max_width'] ) ) {
		        $max_width = intval( $field_data['max_width'] );
	        } else {
		        $max_width = null;
	        }
	        if ( isset( $field_data['max_height'] ) && is_numeric( $field_data['max_height'] ) ) {
		        $max_height = intval( $field_data['max_height'] );
	        } else {
		        $max_height = null;
	        }

	        if ( isset( $field_data['generic_type'] ) ) {
		        $generic_type = intval( $field_data['generic_type'] );
	        } else {
		        $generic_type = null;
	        }

            $fieldObj = $this->_translate_field_factory->cred_translate_field($name, $field, array(
                'class' => $class,
				'preset_value' => $default,
				'preview' => $filtered_attributes['preview'],
				'previewsize' => $filtered_attributes['previewsize'],
                'urlparam' => $filtered_attributes['urlparam'],
                'generic_type' => $generic_type)
            );
        }
        else if ($filtered_attributes['type'] == 'hidden') {
	        if ( isset( $field_data['generic_type'] ) ) {
		        $generic_type = intval( $field_data['generic_type'] );
	        } else {
		        $generic_type = null;
	        }

            $fieldObj = $this->_translate_field_factory->cred_translate_field($name, $field, array(
                'class' => $class,
                'preset_value' => $default,
                'urlparam' => $filtered_attributes['urlparam'],
                'generic_type' => $generic_type)
            );
        }
        else {
            $fieldObj = $this->_translate_field_factory->cred_translate_field($name, $field, array(
                'class' => $class,
                'preset_value' => $default,
                'cred_generic' => 1,
				'placeholder' => $filtered_attributes['placeholder'],
				'preview' => $filtered_attributes['preview'],
				'previewsize' => $filtered_attributes['previewsize'],
                'urlparam' => $filtered_attributes['urlparam']));
        }

        if ($field['data']['persist']) {
            // this field is going to be saved as custom field to db
            CRED_StaticClass::$out['fields']['post_fields'][$name] = $field;
        }

	    if ( array_key_exists( 'default_checked', $fieldObj['data'] )
		    && $fieldObj['data']['default_checked'] == 1 ) {
	        $fieldObj['attr']['default_checked'] = 1;
        }

	    $basic_field_values =  array(
		    'type' => $field['type'],
		    'repetitive' => (isset($field['data']['repetitive']) && $field['data']['repetitive']),
		    'plugin_type' => (isset($field['plugin_type'])) ? $field['plugin_type'] : '',
		    'name' => $name
	    );
	    $translated_field_values = $this->_translate_field_factory->get_html_form_field_id( $field );

	    CRED_StaticClass::$out['form_fields'][$name] = $translated_field_values;
	    CRED_StaticClass::$out['current_form_fields'][$name] = $translated_field_values;
        CRED_StaticClass::$out['form_fields_info'][$name] = $basic_field_values;

	    if ( isset( $translated_field_values ) ) {
		    $basic_field_values['id'] = $translated_field_values;
	    }
	    CRED_StaticClass::$out['generic_fields'][$name] = $basic_field_values;

	    if ( ! empty( $atts['class'] ) ) {
		    $atts['class'] = esc_attr( $atts['class'] );
	    }

	    //Do not delete this commented code, it is a new feature we will enable on the next future release
	    //CRED_Select2_Utils::get_instance()->try_register_field_as_select2( $this->cred_form_rendering->html_form_id, $name, $field, $use_select2 );

        return $this->cred_form_rendering->renderField($fieldObj);
	}

	/**
	 * Maybe get default values for the generic field from the list of options, if it has some.
	 *
	 * Usually, generic fields get their default value from a "default" entry in the JSON object in their content;
	 * however, we can also pass default values as an extra "default: true" pair inside each parsed option.
	 * This way, users can set options and defaults in a single shortcode providing both.
	 *
	 * @param array $field_data
	 * @return array
	 * @since 2.6
	 */
	private function maybe_parse_defaults_from_options( $field_data ) {
		$current_default = toolset_getarr( $field_data, 'default' );
		if ( ! empty( $current_default ) ) {
			return $field_data;
		}

		$current_options = toolset_getarr( $field_data, 'options' );
		$defaults_from_options = array();
		foreach ( $current_options as $option_data ) {
			if ( toolset_getarr( $option_data, 'default', false ) ) {
				$defaults_from_options[] = toolset_getarr( $option_data, 'value' );
			}
		}

		if ( ! empty( $defaults_from_options ) ) {
			$field_data['default'] = $defaults_from_options;
		}

		return $field_data;
	}
}
